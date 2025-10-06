# Git Worktrees - PDF Development Workflow

## Overview
We use git worktrees to enable parallel development on different PDF features without branch switching overhead.

## Active Worktrees

### 1. **Main** (`/Users/andrewphan/tcsadmin/aureuserp`)
- **Branch**: `master`
- **Purpose**: Production code, integration, and PR reviews
- **Use for**: Merging completed features, reviewing code, testing integration

### 2. **PDF Processing** (`/Users/andrewphan/tcsadmin/aureuserp-pdf-processing`)
- **Branch**: `feature/pdf-processing`
- **Purpose**: Backend PDF processing pipeline
- **Focus Areas**:
  - PDF page extraction and thumbnails
  - Text extraction and OCR
  - Metadata parsing
  - Document indexing
  - Background job processing

### 3. **PDF Viewer** (`/Users/andrewphan/tcsadmin/aureuserp-pdf-viewer`)
- **Branch**: `feature/pdf-viewer-enhancements`
- **Purpose**: Nutrient SDK frontend improvements
- **Focus Areas**:
  - Viewer UI/UX enhancements
  - Toolbar customization
  - Performance optimization
  - Mobile responsiveness
  - Keyboard shortcuts

### 4. **PDF Annotations** (`/Users/andrewphan/tcsadmin/aureuserp-pdf-annotations`)
- **Branch**: `feature/pdf-annotations`
- **Purpose**: Annotation features and collaboration
- **Focus Areas**:
  - Annotation types (measurements, callouts, stamps)
  - Real-time collaboration
  - Annotation permissions
  - Export with annotations
  - Annotation search

## Workflow

### Starting Work

```bash
# Work on PDF processing backend
cd ~/tcsadmin/aureuserp-pdf-processing

# Work on viewer frontend
cd ~/tcsadmin/aureuserp-pdf-viewer

# Work on annotations
cd ~/tcsadmin/aureuserp-pdf-annotations
```

### Development Cycle

1. **Make changes** in your worktree
2. **Commit frequently** with descriptive messages
3. **Push to remote** to backup and enable CI/CD
4. **Test** in your isolated environment
5. **Merge to master** when feature is complete

### Managing Worktrees

```bash
# List all worktrees
git worktree list

# Create a new worktree
git worktree add -b feature/new-feature ../aureuserp-new-feature

# Remove a worktree (must be done from main repo)
cd ~/tcsadmin/aureuserp
git worktree remove ../aureuserp-pdf-processing

# Prune deleted worktrees
git worktree prune
```

### Syncing with Master

```bash
# From your worktree
git fetch origin
git merge origin/master

# Or rebase (cleaner history)
git rebase origin/master
```

### Merging to Master

```bash
# Option 1: Via GitHub PR (recommended)
git push origin feature/pdf-processing
# Create PR on GitHub, review, merge

# Option 2: Local merge
cd ~/tcsadmin/aureuserp  # Switch to main worktree
git merge feature/pdf-processing
git push origin master
```

## Best Practices

### Commits
- ✅ Small, focused commits
- ✅ Descriptive commit messages
- ✅ Reference issue/task numbers
- ❌ Don't commit broken code
- ❌ Don't mix unrelated changes

### Branches
- Keep feature branches **short-lived** (< 2 weeks)
- Merge to master **frequently**
- Delete branches after merging
- Use **descriptive branch names**

### Collaboration
- **Push daily** to backup work
- **Pull before starting** each day
- **Communicate** when working on shared files
- **Review PRs** within 24 hours

### Environment
- Each worktree has its own `.env` (copy from main)
- **Don't share** database connections
- Use **different ports** if running multiple dev servers
- Keep **node_modules** separate (auto-handled)

## Claude Code with Worktrees

### Start Claude in specific worktree
```bash
# Work on PDF processing
cd ~/tcsadmin/aureuserp-pdf-processing
claude

# Work on viewer
cd ~/tcsadmin/aureuserp-pdf-viewer
claude
```

### Multi-Claude workflow
```bash
# Terminal 1: Backend work
cd ~/tcsadmin/aureuserp-pdf-processing && claude

# Terminal 2: Frontend work
cd ~/tcsadmin/aureuserp-pdf-viewer && claude

# Terminal 3: Testing/Integration
cd ~/tcsadmin/aureuserp && claude
```

## Directory Structure

```
~/tcsadmin/
├── aureuserp/                      # Main worktree (master)
├── aureuserp-pdf-processing/       # Backend processing
├── aureuserp-pdf-viewer/           # Frontend viewer
└── aureuserp-pdf-annotations/      # Annotation features
```

## Troubleshooting

### Worktree won't create
```bash
# Ensure you're in the main repo
cd ~/tcsadmin/aureuserp
git worktree add -b feature/name ../path
```

### Accidentally deleted worktree directory
```bash
cd ~/tcsadmin/aureuserp
git worktree prune
git worktree add -b feature/name ../path
```

### Conflicts when merging
```bash
# Pull latest master first
git fetch origin
git merge origin/master

# Resolve conflicts manually
# Then commit
git add .
git commit -m "Merge master and resolve conflicts"
```

### Branch already exists
```bash
# If branch exists remotely, check it out
git worktree add ../path feature/name

# If branch exists locally, delete it first
git branch -D feature/name
git worktree add -b feature/name ../path
```

## Resources

- [Git Worktree Documentation](https://git-scm.com/docs/git-worktree)
- [Atlassian Git Worktree Tutorial](https://www.atlassian.com/git/tutorials/git-worktree)
- Project: `/Users/andrewphan/tcsadmin/aureuserp`
- Task Master: `.taskmaster/` (shared across all worktrees)

---

**Last Updated**: 2025-10-06
**Maintained By**: Development Team
