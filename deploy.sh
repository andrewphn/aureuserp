#!/bin/bash

# Deploy script: Push to remote and pull on staging server
# Usage: ./deploy.sh [commit message]
# If no commit message provided, will just push existing commits

set -e  # Exit on error

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting deployment...${NC}"

# Check if there are uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠️  Warning: You have uncommitted changes.${NC}"
    echo "Do you want to commit them? (y/n)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        if [ -z "$1" ]; then
            echo "Enter commit message:"
            read -r commit_msg
        else
            commit_msg="$1"
        fi
        git add -A
        git commit -m "$commit_msg"
        echo -e "${GREEN}✅ Changes committed${NC}"
    else
        echo -e "${YELLOW}⚠️  Skipping commit. Pushing existing commits...${NC}"
    fi
fi

# Push to remote
echo -e "${BLUE}📤 Pushing to origin/master...${NC}"
if git push; then
    echo -e "${GREEN}✅ Pushed successfully${NC}"
else
    echo -e "${YELLOW}❌ Push failed${NC}"
    exit 1
fi

# Pull on staging server
echo -e "${BLUE}📥 Pulling on staging server...${NC}"
if ssh hg "cd staging.tcswoodwork.com && git pull"; then
    echo -e "${GREEN}✅ Pulled successfully on staging${NC}"
else
    echo -e "${YELLOW}❌ Pull on staging failed${NC}"
    exit 1
fi

echo -e "${GREEN}🎉 Deployment complete!${NC}"
