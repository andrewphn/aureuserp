<?php

namespace App\Services;

use App\Models\MeetingTranscript;
use App\Models\MeetingSegment;
use App\Models\MeetingTopic;
use App\Models\MeetingActionItem;
use App\Models\MeetingEntity;
use App\Models\MeetingSentence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Meeting Transcript Processor class
 *
 */
class MeetingTranscriptProcessor
{
    private const CHUNK_SIZE = 500; // Process 500 sentences at a time
    private const TOPIC_CHANGE_THRESHOLD = 50; // sentences before considering new topic

    private array $participants = [];
    private array $topics = [];
    private array $entities = [];
    private array $actionKeywords = ['need to', 'should', 'must', 'will', 'going to', 'plan to'];

    /**
     * Process
     *
     * @param string $filePath
     * @param ?string $title
     * @return MeetingTranscript
     */
    public function process(string $filePath, ?string $title = null): MeetingTranscript
    {
        // Create the meeting transcript record
        $meeting = MeetingTranscript::create([
            'file_path' => $filePath,
            'title' => $title ?? $this->extractTitleFromPath($filePath),
            'status' => 'processing',
        ]);

        try {
            // Load and process the JSON file
            $jsonContent = File::get($filePath);
            $sentences = json_decode($jsonContent, true);

            if (!is_array($sentences)) {
                throw new \Exception('Invalid JSON format');
            }

            $totalSentences = count($sentences);
            $meeting->update(['total_sentences' => $totalSentences]);

            // Extract participants
            $this->extractParticipants($sentences);
            $meeting->update(['participants' => $this->participants]);

            // Calculate duration
            $duration = $this->calculateDuration($sentences);
            $meeting->update(['duration_minutes' => $duration]);

            // Process in chunks
            $chunks = array_chunk($sentences, self::CHUNK_SIZE);
            $currentSegment = null;
            $currentTopic = null;
            $sequenceNumber = 1;

            foreach ($chunks as $chunkIndex => $chunk) {
                $this->processChunk($meeting, $chunk, $sequenceNumber, $currentSegment, $currentTopic);
                $sequenceNumber += count($chunk);
            }

            // Extract entities from all sentences
            $this->extractEntities($meeting);

            // Generate summary
            $summary = $this->generateSummary($meeting);
            $meeting->update([
                'summary' => $summary,
                'status' => 'completed',
            ]);

            // Generate index files
            $this->generateIndexFiles($meeting);

            return $meeting;
        } catch (\Exception $e) {
            $meeting->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Process Chunk
     *
     * @param MeetingTranscript $meeting
     * @param array $chunk
     * @return void
     */
    private function processChunk(
        MeetingTranscript $meeting,
        array $chunk,
        int &$sequenceNumber,
        ?MeetingSegment &$currentSegment,
        ?string &$currentTopic
    ): void {
        foreach ($chunk as $index => $sentenceData) {
            // Store the sentence
            $sentence = MeetingSentence::create([
                'meeting_transcript_id' => $meeting->id,
                'meeting_segment_id' => $currentSegment?->id,
                'speaker_name' => $sentenceData['speaker_name'] ?? 'Unknown',
                'speaker_id' => $sentenceData['speaker_id'] ?? 0,
                'start_time' => $sentenceData['startTime'] ?? '00:00',
                'end_time' => $sentenceData['endTime'] ?? '00:00',
                'sentence' => $sentenceData['sentence'] ?? '',
                'sequence_number' => $sequenceNumber,
            ]);

            // Detect topic changes (simple heuristic based on keywords)
            $detectedTopic = $this->detectTopic($sentenceData['sentence'] ?? '');

            if ($detectedTopic !== $currentTopic && $detectedTopic !== null) {
                // Create new segment
                $currentSegment = $this->createSegment(
                    $meeting,
                    $detectedTopic,
                    $sentenceData['startTime'] ?? '00:00',
                    $this->getSpeakers($chunk, $index)
                );
                $currentTopic = $detectedTopic;

                // Update sentence with segment
                $sentence->update(['meeting_segment_id' => $currentSegment->id]);

                // Create or update topic
                $this->createOrUpdateTopic($meeting, $detectedTopic, $sentenceData['startTime'] ?? '00:00');
            }

            // Extract action items
            if ($this->containsActionKeyword($sentenceData['sentence'] ?? '')) {
                $this->extractActionItem($meeting, $sentenceData);
            }

            $sequenceNumber++;
        }
    }

    /**
     * Extract Participants
     *
     * @param array $sentences
     * @return void
     */
    private function extractParticipants(array $sentences): void
    {
        $speakers = [];
        foreach ($sentences as $sentence) {
            $name = $sentence['speaker_name'] ?? 'Unknown';
            $id = $sentence['speaker_id'] ?? 0;

            if (!isset($speakers[$id])) {
                $speakers[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'sentence_count' => 0,
                ];
            }
            $speakers[$id]['sentence_count']++;
        }

        $this->participants = array_values($speakers);
    }

    /**
     * Calculate Duration
     *
     * @param array $sentences
     * @return int
     */
    private function calculateDuration(array $sentences): int
    {
        if (empty($sentences)) {
            return 0;
        }

        $lastSentence = end($sentences);
        $endTime = $lastSentence['endTime'] ?? '00:00';

        // Convert time format "HH:MM" or "MM:SS" to minutes
        $parts = explode(':', $endTime);
        if (count($parts) === 2) {
            return (int)$parts[0]; // Assume format is MM:SS, return minutes
        }

        return 0;
    }

    /**
     * Detect Topic
     *
     * @param string $sentence
     * @return ?string
     */
    private function detectTopic(string $sentence): ?string
    {
        $keywords = [
            'inventory' => ['inventory', 'stock', 'warehouse', 'materials', 'supplies'],
            'sourcing' => ['sourcing', 'purchasing', 'order', 'vendor', 'supplier'],
            'cabinet' => ['cabinet', 'drawer', 'door', 'face frame', 'panel'],
            'production' => ['production', 'build', 'assembly', 'fabrication', 'cnc'],
            'design' => ['design', 'rhino', 'dwg', 'drawing', 'specification'],
            'training' => ['training', 'teach', 'learn', 'orientation', 'demonstrate'],
            'workflow' => ['workflow', 'process', 'pipeline', 'handoff', 'procedure'],
            'pricing' => ['pricing', 'linear feet', 'cost', 'budget', 'estimate'],
            'delivery' => ['delivery', 'shipping', 'transport', 'install'],
            'quality' => ['quality', 'qc', 'inspection', 'standard'],
        ];

        $sentenceLower = strtolower($sentence);

        foreach ($keywords as $topic => $words) {
            foreach ($words as $word) {
                if (str_contains($sentenceLower, $word)) {
                    return $topic;
                }
            }
        }

        return null;
    }

    /**
     * Create Segment
     *
     * @param MeetingTranscript $meeting
     * @param string $topic
     * @param string $startTime
     * @param array $speakers
     * @return MeetingSegment
     */
    private function createSegment(
        MeetingTranscript $meeting,
        string $topic,
        string $startTime,
        array $speakers
    ): MeetingSegment {
        return MeetingSegment::create([
            'meeting_transcript_id' => $meeting->id,
            'start_time' => $startTime,
            'end_time' => $startTime, // Will be updated when segment ends
            'topic' => ucfirst($topic),
            'summary' => "Discussion about {$topic}",
            'speakers' => $speakers,
            'sentence_count' => 0,
        ]);
    }

    /**
     * Get Speakers
     *
     * @param array $chunk
     * @param int $fromIndex
     * @return array
     */
    private function getSpeakers(array $chunk, int $fromIndex): array
    {
        $speakers = [];
        $endIndex = min($fromIndex + 50, count($chunk));

        for ($i = $fromIndex; $i < $endIndex; $i++) {
            if (isset($chunk[$i]['speaker_name'])) {
                $speakers[] = $chunk[$i]['speaker_name'];
            }
        }

        return array_values(array_unique($speakers));
    }

    /**
     * Create Or Update Topic
     *
     * @param MeetingTranscript $meeting
     * @param string $topicName
     * @param string $time
     * @return void
     */
    private function createOrUpdateTopic(MeetingTranscript $meeting, string $topicName, string $time): void
    {
        $existing = MeetingTopic::where('meeting_transcript_id', $meeting->id)
            ->where('topic_name', ucfirst($topicName))
            ->first();

        if ($existing) {
            $existing->increment('mention_count');
        } else {
            MeetingTopic::create([
                'meeting_transcript_id' => $meeting->id,
                'topic_name' => ucfirst($topicName),
                'description' => "Discussion about {$topicName}",
                'first_mention_time' => $time,
                'keywords' => [$topicName],
                'mention_count' => 1,
            ]);
        }
    }

    /**
     * Contains Action Keyword
     *
     * @param string $sentence
     * @return bool
     */
    private function containsActionKeyword(string $sentence): bool
    {
        $sentenceLower = strtolower($sentence);

        foreach ($this->actionKeywords as $keyword) {
            if (str_contains($sentenceLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract Action Item
     *
     * @param MeetingTranscript $meeting
     * @param array $sentenceData
     * @return void
     */
    private function extractActionItem(MeetingTranscript $meeting, array $sentenceData): void
    {
        $sentence = $sentenceData['sentence'] ?? '';
        $speaker = $sentenceData['speaker_name'] ?? 'Unknown';
        $time = $sentenceData['startTime'] ?? '00:00';

        // Simple extraction - you can enhance this with NLP
        MeetingActionItem::create([
            'meeting_transcript_id' => $meeting->id,
            'assignee' => $speaker,
            'task' => $sentence,
            'priority' => 'medium',
            'status' => 'pending',
            'mentioned_at_time' => $time,
            'context' => $sentence,
        ]);
    }

    /**
     * Extract Entities
     *
     * @param MeetingTranscript $meeting
     * @return void
     */
    private function extractEntities(MeetingTranscript $meeting): void
    {
        // Get all sentences for this meeting
        $sentences = MeetingSentence::where('meeting_transcript_id', $meeting->id)->get();

        $entities = [
            'person' => [],
            'project' => [],
            'process' => [],
            'tool' => [],
        ];

        // Known entities from the transcript
        $knownPeople = ['Bryan', 'Andrew', 'Aiden', 'Sadie', 'Alina', 'Levi', 'Shaggy', 'Dagger', 'Chase', 'Shane'];
        $knownTools = ['Rhino', 'CNC', 'V-Carve', 'Notion', 'Blum'];
        $knownProjects = ['Sankity'];

        foreach ($sentences as $sentence) {
            $text = $sentence->sentence;

            // Extract people
            foreach ($knownPeople as $person) {
                if (str_contains($text, $person)) {
                    if (!isset($entities['person'][$person])) {
                        $entities['person'][$person] = ['count' => 0, 'contexts' => []];
                    }
                    $entities['person'][$person]['count']++;
                    $entities['person'][$person]['contexts'][] = substr($text, 0, 200);
                }
            }

            // Extract tools
            foreach ($knownTools as $tool) {
                if (str_contains($text, $tool)) {
                    if (!isset($entities['tool'][$tool])) {
                        $entities['tool'][$tool] = ['count' => 0, 'contexts' => []];
                    }
                    $entities['tool'][$tool]['count']++;
                    $entities['tool'][$tool]['contexts'][] = substr($text, 0, 200);
                }
            }

            // Extract projects
            foreach ($knownProjects as $project) {
                if (str_contains($text, $project)) {
                    if (!isset($entities['project'][$project])) {
                        $entities['project'][$project] = ['count' => 0, 'contexts' => []];
                    }
                    $entities['project'][$project]['count']++;
                    $entities['project'][$project]['contexts'][] = substr($text, 0, 200);
                }
            }
        }

        // Store entities in database
        foreach ($entities as $type => $items) {
            foreach ($items as $name => $data) {
                MeetingEntity::create([
                    'meeting_transcript_id' => $meeting->id,
                    'entity_type' => $type,
                    'entity_name' => $name,
                    'mentions_count' => $data['count'],
                    'context_snippets' => array_slice($data['contexts'], 0, 10), // Keep first 10 contexts
                ]);
            }
        }
    }

    /**
     * Generate Summary
     *
     * @param MeetingTranscript $meeting
     * @return string
     */
    private function generateSummary(MeetingTranscript $meeting): string
    {
        $topics = MeetingTopic::where('meeting_transcript_id', $meeting->id)
            ->orderBy('mention_count', 'desc')
            ->get();

        $entities = MeetingEntity::where('meeting_transcript_id', $meeting->id)
            ->where('entity_type', 'person')
            ->orderBy('mentions_count', 'desc')
            ->get();

        $participants = $entities->pluck('entity_name')->take(5)->join(', ');
        $mainTopics = $topics->take(5)->pluck('topic_name')->join(', ');

        return "Meeting with {$participants} covering topics: {$mainTopics}. " .
               "Duration: {$meeting->duration_minutes} minutes. " .
               "Total sentences: {$meeting->total_sentences}.";
    }

    /**
     * Extract Title From Path
     *
     * @param string $path
     * @return string
     */
    private function extractTitleFromPath(string $path): string
    {
        $filename = basename($path, '.json');
        return str_replace(['-', '_'], ' ', $filename);
    }

    /**
     * Generate Index Files
     *
     * @param MeetingTranscript $meeting
     * @return void
     */
    private function generateIndexFiles(MeetingTranscript $meeting): void
    {
        $basePath = dirname($meeting->file_path);
        $meetingFolder = $basePath . '/' . pathinfo($meeting->file_path, PATHINFO_FILENAME);

        // Create directory structure
        if (!File::exists($meetingFolder)) {
            File::makeDirectory($meetingFolder, 0755, true);
        }
        File::makeDirectory($meetingFolder . '/segments', 0755, true);
        File::makeDirectory($meetingFolder . '/topics', 0755, true);

        // Generate index.md
        $this->generateIndexMd($meeting, $meetingFolder);

        // Generate full transcript
        $this->generateFullTranscript($meeting, $meetingFolder);

        // Generate segment files
        $this->generateSegmentFiles($meeting, $meetingFolder);

        // Generate topic files
        $this->generateTopicFiles($meeting, $meetingFolder);

        // Generate action items file
        $this->generateActionItemsFile($meeting, $meetingFolder);
    }

    /**
     * Generate Index Md
     *
     * @param MeetingTranscript $meeting
     * @param string $folder
     * @return void
     */
    private function generateIndexMd(MeetingTranscript $meeting, string $folder): void
    {
        $content = "# {$meeting->title}\n\n";
        $content .= "**Date:** {$meeting->meeting_date}\n";
        $content .= "**Duration:** {$meeting->duration_minutes} minutes\n";
        $content .= "**Status:** {$meeting->status}\n\n";

        $content .= "## Summary\n\n";
        $content .= $meeting->summary . "\n\n";

        $content .= "## Participants\n\n";
        foreach ($meeting->participants as $participant) {
            $content .= "- {$participant['name']} ({$participant['sentence_count']} statements)\n";
        }

        $content .= "\n## Topics Covered\n\n";
        foreach ($meeting->topics()->orderBy('mention_count', 'desc')->get() as $topic) {
            $content .= "- [{$topic->topic_name}](topics/{$topic->topic_name}.md) (mentioned {$topic->mention_count} times)\n";
        }

        $content .= "\n## Timeline\n\n";
        foreach ($meeting->segments()->orderBy('start_time')->get() as $segment) {
            $content .= "- [{$segment->start_time} - {$segment->end_time}](segments/{$segment->id}.md) - {$segment->topic}\n";
        }

        File::put($folder . '/index.md', $content);
    }

    /**
     * Generate Full Transcript
     *
     * @param MeetingTranscript $meeting
     * @param string $folder
     * @return void
     */
    private function generateFullTranscript(MeetingTranscript $meeting, string $folder): void
    {
        $content = "";
        foreach ($meeting->sentences()->orderBy('sequence_number')->get() as $sentence) {
            $content .= "{$sentence->speaker_name} ({$sentence->start_time}-{$sentence->end_time}): {$sentence->sentence}\n";
        }

        File::put($folder . '/full-transcript.txt', $content);
    }

    /**
     * Generate Segment Files
     *
     * @param MeetingTranscript $meeting
     * @param string $folder
     * @return void
     */
    private function generateSegmentFiles(MeetingTranscript $meeting, string $folder): void
    {
        foreach ($meeting->segments as $segment) {
            $content = "# {$segment->topic}\n\n";
            $content .= "**Time:** {$segment->start_time} - {$segment->end_time}\n";
            $content .= "**Speakers:** " . implode(', ', $segment->speakers) . "\n\n";
            $content .= "## Summary\n\n{$segment->summary}\n\n";
            $content .= "## Transcript\n\n";

            foreach ($segment->sentences()->orderBy('sequence_number')->get() as $sentence) {
                $content .= "**{$sentence->speaker_name}** ({$sentence->start_time}): {$sentence->sentence}\n\n";
            }

            File::put($folder . "/segments/{$segment->id}.md", $content);
        }
    }

    /**
     * Generate Topic Files
     *
     * @param MeetingTranscript $meeting
     * @param string $folder
     * @return void
     */
    private function generateTopicFiles(MeetingTranscript $meeting, string $folder): void
    {
        foreach ($meeting->topics as $topic) {
            $content = "# {$topic->topic_name}\n\n";
            $content .= "**First Mentioned:** {$topic->first_mention_time}\n";
            $content .= "**Total Mentions:** {$topic->mention_count}\n\n";
            $content .= "## Description\n\n{$topic->description}\n\n";

            File::put($folder . "/topics/{$topic->topic_name}.md", $content);
        }
    }

    /**
     * Generate Action Items File
     *
     * @param MeetingTranscript $meeting
     * @param string $folder
     * @return void
     */
    private function generateActionItemsFile(MeetingTranscript $meeting, string $folder): void
    {
        $content = "# Action Items\n\n";

        foreach ($meeting->actionItems()->orderBy('mentioned_at_time')->get() as $item) {
            $content .= "## {$item->assignee} - {$item->mentioned_at_time}\n\n";
            $content .= "**Status:** {$item->status}\n";
            $content .= "**Priority:** {$item->priority}\n\n";
            $content .= "{$item->task}\n\n";
            $content .= "---\n\n";
        }

        File::put($folder . '/action-items.md', $content);
    }
}
