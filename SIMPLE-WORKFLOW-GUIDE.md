# 25 Friendship Lane - Simple Manual Workflow

**What You Need to Do:** Create 2 rooms (Main Kitchen + Pantry) and their cabinet runs

---

## Option 1: Use Wizard (Recommended - No Broken Tools)

**URL:** `http://aureuserp.test/admin/project/projects/1/review-pdf-and-price?pdf=1`

### Page 1 - Cover
1. Set Page Type: Cover
2. Click blue Annotate button

### Page 2 - Floor Plan
1. Click "New Room"
   - Room Type: Kitchen
   - Let it auto-name or call it "Main Kitchen"
2. Click "Add Another Room"
   - Room Type: Pantry
   - Let it auto-name or call it "Pantry"
3. Click blue Annotate button

### Pages 3-8 - Just set page types
- Page 3: Elevation
- Page 4: Elevation
- Page 5: Elevation
- Page 6: Elevation
- Page 7: Elevation
- Page 8: Detail

Then click through to Step 2 or go to Project Data tab to add locations and cabinet runs.

---

## Option 2: Direct Database Entry (Fastest)

I can create the rooms and locations directly via PHP artisan tinker if you want to skip the UI entirely.

---

## Option 3: Fix and Use Annotation Viewer

If you really want the annotation viewer to work, I need to:
1. Check what's actually broken with the tools
2. See if it's a JavaScript console error
3. Possibly need to look at the FilamentPHP component code

---

**What would you prefer?**
1. I help you through the wizard manually
2. I create rooms via database directly
3. I investigate why annotation tools are broken

Let me know and I'll stop creating broken Playwright windows!
