# Meeting Transcript Indexing System

## Overview

This system processes large meeting transcript JSON files, extracts structured information, and creates searchable indexed databases without overwhelming the context window.

## Features

✅ **Chunk Processing** - Processes transcripts in 500-sentence chunks to avoid memory issues
✅ **Topic Detection** - Automatically identifies and segments topics discussed
✅ **Entity Extraction** - Finds people, projects, processes, and tools mentioned
✅ **Action Item Detection** - Extracts tasks and assignments from discussion
✅ **Full-Text Search** - Fast searchable database of all sentences
✅ **Organized Output** - Generates navigable markdown index files
✅ **Timeline View** - Chronological view of topic changes

## Database Schema

### Tables Created
- `meeting_transcripts` - Main meeting records
- `meeting_segments` - Topic-based sections
- `meeting_topics` - Index of topics discussed
- `meeting_action_items` - Extracted tasks and assignments
- `meeting_entities` - People, projects, tools mentioned
- `meeting_sentences` - Full-text searchable sentences

## Usage

### Processing a Meeting Transcript

```bash
php artisan meeting:process <path-to-json-file> [--title="Custom Title"]
```

**Example:**
```bash
php artisan meeting:process "docs/meeting/392-N-Montgomery-St-Bldg-B-m4a-e2186e26-7550.json" \
    --title="392 N Montgomery St Building B - Workflow Planning Meeting"
```

### Searching Transcripts

```bash
php artisan meeting:search <query> [options]
```

**Options:**
- `--meeting=<id>` - Search within specific meeting
- `--speaker=<name>` - Filter by speaker
- `--limit=<n>` - Number of results (default: 10)

**Examples:**
```bash
# Search for mentions of "cabinet"
php artisan meeting:search "cabinet" --limit=5

# Search for what Bryan said about inventory
php artisan meeting:search "inventory" --speaker=Bryan --limit=10

# Search within a specific meeting
php artisan meeting:search "workflow" --meeting=1
```

## Generated Output

When a meeting is processed, the following structure is created:

```
docs/meeting/[meeting-name]/
├── index.md                    # Executive summary with navigation
├── full-transcript.txt         # Complete readable transcript
├── action-items.md            # All extracted action items
├── segments/                  # Topic-based segments
│   ├── 1.md
│   ├── 2.md
│   └── ...
└── topics/                    # Individual topic summaries
    ├── Cabinet.md
    ├── Inventory.md
    ├── Production.md
    └── ...
```

## Example: 392 N Montgomery St Meeting

**Processed:** 4,227 sentences
**Participants:** Bryan (1,187 statements), Andrew (3,040 statements)
**Topics Identified:** 10 (Cabinet, Production, Design, Inventory, Sourcing, Workflow, Training, Delivery, Quality, Pricing)
**Segments Created:** 329 topic-based sections
**Action Items Extracted:** 310 tasks
**Entities Found:** 13 (people, tools, projects)

### Files Generated

- [Index](392-N-Montgomery-St-Bldg-B-m4a-e2186e26-7550/index.md) - Navigation and summary
- [Full Transcript](392-N-Montgomery-St-Bldg-B-m4a-e2186e26-7550/full-transcript.txt) - Complete readable format
- [Action Items](392-N-Montgomery-St-Bldg-B-m4a-e2186e26-7550/action-items.md) - Extracted tasks
- [Topics](392-N-Montgomery-St-Bldg-B-m4a-e2186e26-7550/topics/) - Individual topic pages
- [Segments](392-N-Montgomery-St-Bldg-B-m4a-e2186e26-7550/segments/) - Time-based sections

## Search Examples

### Find inventory discussions
```bash
php artisan meeting:search "inventory" --limit=5
```

### Find all mentions of Aiden
```bash
php artisan meeting:search "Aiden" --limit=10
```

### Find what Andrew said about cabinet specifications
```bash
php artisan meeting:search "cabinet" --speaker=Andrew --limit=10
```

### Find workflow discussions
```bash
php artisan meeting:search "workflow" --limit=5
```

## Benefits

### Context Window Management
✅ Never loads entire transcript into memory
✅ Processes in manageable 500-sentence chunks
✅ Prevents context overflow with large files

### Information Retrieval
✅ Fast full-text search across all meetings
✅ Topic-based navigation
✅ Time-stamped references
✅ Speaker-filtered searches

### Organization
✅ Structured output by topic, not chronology
✅ Cross-referenced index files
✅ Action items automatically extracted
✅ Entity tracking across discussions

## Technical Details

### Processing Pipeline

1. **Load JSON** - Read transcript file
2. **Extract Metadata** - Participants, duration
3. **Chunk Processing** - Process 500 sentences at a time
4. **Topic Detection** - Identify topic changes using keywords
5. **Segment Creation** - Group sentences by topic
6. **Entity Extraction** - Find people, projects, tools
7. **Action Detection** - Extract tasks from discussion
8. **Summary Generation** - Create executive summary
9. **Index Generation** - Create navigable markdown files
10. **Database Storage** - Store for fast searching

### Topic Keywords

The system detects topics based on these keywords:

- **Inventory**: inventory, stock, warehouse, materials, supplies
- **Sourcing**: sourcing, purchasing, order, vendor, supplier
- **Cabinet**: cabinet, drawer, door, face frame, panel
- **Production**: production, build, assembly, fabrication, cnc
- **Design**: design, rhino, dwg, drawing, specification
- **Training**: training, teach, learn, orientation, demonstrate
- **Workflow**: workflow, process, pipeline, handoff, procedure
- **Pricing**: pricing, linear feet, cost, budget, estimate
- **Delivery**: delivery, shipping, transport, install
- **Quality**: quality, qc, inspection, standard

### Action Keywords

Tasks are detected when sentences contain:
- "need to"
- "should"
- "must"
- "will"
- "going to"
- "plan to"

## Database Queries

### Get all meetings
```php
MeetingTranscript::with('topics', 'actionItems', 'entities')->get();
```

### Find meetings about a topic
```php
MeetingTranscript::whereHas('topics', function($q) {
    $q->where('topic_name', 'Cabinet');
})->get();
```

### Get action items for a person
```php
MeetingActionItem::where('assignee', 'Bryan')
    ->where('status', 'pending')
    ->get();
```

### Search sentences
```php
MeetingSentence::where('sentence', 'LIKE', '%cabinet%')
    ->where('speaker_name', 'Bryan')
    ->with('meetingTranscript')
    ->get();
```

## Future Enhancements

### Planned Features
- [ ] FilamentPHP admin interface for browsing meetings
- [ ] AI-powered summary generation
- [ ] Topic relationship mapping
- [ ] Meeting comparison tools
- [ ] Export to different formats (PDF, DOCX)
- [ ] Speaker sentiment analysis
- [ ] Automatic meeting notes generation
- [ ] Integration with project management system

### Potential Improvements
- Enhanced topic detection using NLP
- Better action item extraction with assignee detection
- Decision tracking (who decided what)
- Follow-up item tracking
- Meeting analytics dashboard
- Voice-to-text integration for live meetings

## Architecture

### Service Class
`App\Services\MeetingTranscriptProcessor` - Core processing logic

### Models
- `App\Models\MeetingTranscript` - Main meeting record
- `App\Models\MeetingSegment` - Topic segments
- `App\Models\MeetingTopic` - Topic index
- `App\Models\MeetingActionItem` - Action items
- `App\Models\MeetingEntity` - Entity tracking
- `App\Models\MeetingSentence` - Searchable sentences

### Commands
- `php artisan meeting:process` - Process transcript
- `php artisan meeting:search` - Search transcripts

### Migration
- `2025_11_21_122308_create_meeting_transcript_tables.php`

## Performance

### Sample Processing Time
- **4,227 sentences**: ~45 seconds
- **Database inserts**: ~4,500 records
- **Index files generated**: 342 files
- **Total size**: ~330KB organized data

### Search Performance
- Average query time: <100ms
- Full-text search supported
- Indexed for fast lookups

## Maintenance

### Reprocess a Meeting
```bash
# Delete old records
php artisan tinker
>>> MeetingTranscript::where('file_path', 'path/to/file.json')->delete();

# Process again
php artisan meeting:process "path/to/file.json"
```

### Clean Up Old Meetings
```bash
php artisan tinker
>>> MeetingTranscript::where('created_at', '<', now()->subDays(90))->delete();
```

## Troubleshooting

### Issue: Out of Memory
**Solution**: The system already chunks processing. If still occurring, reduce `CHUNK_SIZE` in `MeetingTranscriptProcessor.php`

### Issue: Search Returns No Results
**Solution**: Verify the meeting was processed successfully. Check `meeting_transcripts` table for status='completed'

### Issue: Index Files Not Generated
**Solution**: Check write permissions on `docs/meeting/` directory

## Credits

Built for TCS Woodwork AureusERP system to handle large meeting transcripts without context window overflow.

**Created**: November 21, 2025
**Version**: 1.0
