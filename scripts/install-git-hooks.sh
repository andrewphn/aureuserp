#!/bin/bash
# Install git hooks to prevent committing storage files

HOOKS_DIR=".git/hooks"
PRE_COMMIT_HOOK="$HOOKS_DIR/pre-commit"

# Create hooks directory if it doesn't exist
mkdir -p "$HOOKS_DIR"

# Create pre-commit hook
cat > "$PRE_COMMIT_HOOK" << 'EOF'
#!/bin/bash
# Pre-commit hook to prevent committing storage files

# Check if any storage/app/public files are being committed (except .gitkeep)
if git diff --cached --name-only | grep -E '^storage/app/public/' | grep -v '\.gitkeep$'; then
    echo "❌ ERROR: Attempting to commit storage files!"
    echo ""
    echo "Storage files should NOT be committed to git."
    echo "These files are user-uploaded media and should remain on the server only."
    echo ""
    echo "Files being committed:"
    git diff --cached --name-only | grep -E '^storage/app/public/' | grep -v '\.gitkeep$'
    echo ""
    echo "If you need to commit .gitkeep, use: git add -f storage/app/public/.gitkeep"
    exit 1
fi

exit 0
EOF

# Make hook executable
chmod +x "$PRE_COMMIT_HOOK"

echo "✅ Git hooks installed successfully!"
echo "   Pre-commit hook will now prevent committing storage files."
