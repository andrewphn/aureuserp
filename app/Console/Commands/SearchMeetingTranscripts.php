<?php

namespace App\Console\Commands;

use App\Models\MeetingSentence;
use App\Models\MeetingTranscript;
use Illuminate\Console\Command;

/**
 * Search Meeting Transcripts class
 *
 */
class SearchMeetingTranscripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:search
                            {query : The search query}
                            {--meeting= : Optional meeting ID to search within}
                            {--speaker= : Optional speaker name to filter by}
                            {--limit=10 : Number of results to return}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search through meeting transcripts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = $this->argument('query');
        $meetingId = $this->option('meeting');
        $speaker = $this->option('speaker');
        $limit = (int) $this->option('limit');

        $this->info("Searching for: \"{$query}\"");
        $this->newLine();

        // Build query
        $results = MeetingSentence::query()
            ->where('sentence', 'LIKE', "%{$query}%")
            ->when($meetingId, function ($q) use ($meetingId) {
                return $q->where('meeting_transcript_id', $meetingId);
            })
            ->when($speaker, function ($q) use ($speaker) {
                return $q->where('speaker_name', 'LIKE', "%{$speaker}%");
            })
            ->with(['meetingTranscript', 'meetingSegment'])
            ->orderBy('meeting_transcript_id')
            ->orderBy('sequence_number')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            $this->warn("No results found for: \"{$query}\"");
            return Command::SUCCESS;
        }

        $this->info("Found {$results->count()} results:");
        $this->newLine();

        foreach ($results as $result) {
            $this->displayResult($result);
        }

        return Command::SUCCESS;
    }

    /**
     * Display Result
     *
     * @param MeetingSentence $sentence
     * @return void
     */
    private function displayResult(MeetingSentence $sentence): void
    {
        $meeting = $sentence->meetingTranscript;
        $segment = $sentence->meetingSegment;

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("<fg=cyan>Meeting:</> {$meeting->title}");
        $this->line("<fg=cyan>Time:</> {$sentence->start_time} - {$sentence->end_time}");
        if ($segment) {
            $this->line("<fg=cyan>Topic:</> {$segment->topic}");
        }
        $this->line("<fg=cyan>Speaker:</> {$sentence->speaker_name}");
        $this->newLine();
        $this->line($sentence->sentence);
        $this->newLine();
    }
}
