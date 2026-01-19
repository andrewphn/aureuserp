# TCS Grasshopper Cabinet System - Installation Guide

## Prerequisites

### Required Software
1. **Rhino 7 or 8** (Windows or Mac)
2. **Grasshopper** (included with Rhino)
3. **Human UI Plugin** (for interactive panels)

### Required Access
1. **TCS ERP API Token** from aureuserp.test admin panel
2. **Network access** to API server (local dev or staging)

---

## Step 1: Install Human UI Plugin

Human UI provides the interactive UI components (dropdowns, sliders, tables, buttons).

1. Go to: https://www.food4rhino.com/en/app/human-ui
2. Download the latest version for your Rhino version
3. Run the installer or extract to Grasshopper Libraries folder:
   - **Windows**: `%APPDATA%\Grasshopper\Libraries\`
   - **Mac**: `~/Library/Application Support/Grasshopper/Libraries/`
4. Restart Rhino

### Verify Human UI Installation
1. Open Rhino
2. Type `Grasshopper` to open Grasshopper
3. Look for "Human UI" tab in the component toolbar
4. If missing, check the Libraries folder path

---

## Step 2: Copy TCS Components

### Option A: Reference Files Directly (Recommended for Development)

Keep files in the repository and reference them in GHPython components:

```
/Users/andrewphan/tcsadmin/aureuserp/rhino-plugins/tcs-grasshopper/
├── api/
├── navigation/
├── calculator/
├── geometry/
└── ui/
```

### Option B: Install as User Objects (For Production)

Copy to Grasshopper User Objects folder:

**Windows:**
```
%APPDATA%\Grasshopper\UserObjects\TCS\
```

**Mac:**
```
~/Library/Application Support/Grasshopper/UserObjects/TCS/
```

---

## Step 3: Get API Token

1. Log in to TCS ERP: http://aureuserp.test/admin
   - Email: `info@tcswoodwork.com`
   - Password: `Lola2024!`

2. Go to **Settings** → **API Tokens** (or User Profile → API Tokens)

3. Create new token:
   - Name: "Grasshopper Integration"
   - Abilities: Select all or specific permissions
   - Click "Create"

4. **Copy the token immediately** - it won't be shown again!

5. Store token securely (you'll paste it into Grasshopper)

---

## Step 4: Verify Network Access

Test API connectivity before opening Grasshopper:

### From Terminal/Command Prompt:
```bash
# Test local dev server
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -H "Accept: application/json" \
     http://aureuserp.test/api/v1/projects

# Expected: JSON response with projects list
```

### Common Issues:
- **Connection refused**: Laravel Herd not running
- **401 Unauthorized**: Invalid or expired token
- **404 Not Found**: Wrong URL or route not defined

---

## Step 5: First Launch Test

1. Open Rhino
2. Type `Grasshopper` command
3. Create new definition (Ctrl+N)
4. Add a **GHPython** component (Math tab → Script → GHPython)
5. Double-click to open editor
6. Paste contents of `api/tcs_api_connect.py`
7. Close editor
8. Right-click component → Rename inputs/outputs:
   - Inputs: `api_url`, `api_token`, `test_connection`
   - Outputs: `connected`, `auth_header`, `api_base`, `status_msg`, `error`

9. Add input panels:
   - Text Panel with: `http://aureuserp.test`
   - Text Panel with: Your API token
   - Boolean Toggle for test_connection

10. Connect and toggle test_connection to `True`

11. Check outputs:
    - `connected` should be `True`
    - `status_msg` should show "Connected - X projects available"

---

## Troubleshooting Installation

### "No module named urllib2"
- You're using Python 3. GHPython in Rhino 7+ uses IronPython 2.7
- The components are written for IronPython 2.7 compatibility

### "SSL Certificate Error"
- The components disable SSL verification for local development
- This is intentional for `aureuserp.test` domain
- For production, update the SSL context settings

### "Human UI components not found"
1. Verify Human UI is in Libraries folder
2. Unblock the DLL files (Windows): Right-click → Properties → Unblock
3. Restart Rhino completely

### "Component outputs are all None"
1. Check the component's output panel (bottom) for error messages
2. Verify input connections are correct
3. Check that input values are not empty strings

---

## Next Steps

After successful installation:
1. Follow [QUICK_START_GUIDE.md](QUICK_START_GUIDE.md) for first project
2. See [SOP_CABINET_DESIGN.md](SOP_CABINET_DESIGN.md) for full workflow
3. Reference [COMPONENT_REFERENCE.md](COMPONENT_REFERENCE.md) for details
