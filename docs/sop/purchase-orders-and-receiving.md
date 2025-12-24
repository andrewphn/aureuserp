# SOP: Purchase Orders & Receiving

> **Purpose**: Standard Operating Procedure for creating Purchase Orders and receiving materials into inventory in AureusERP.
>
> **Last Updated**: December 24, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Creating a Purchase Order](#part-1-creating-a-purchase-order)
3. [Receiving Materials](#part-2-receiving-materials)
4. [Partial Receipts](#part-3-partial-receipts)
5. [Creating Vendor Bills](#part-4-creating-vendor-bills)
6. [Quick Reference](#quick-reference)
7. [Tips & Best Practices](#tips--best-practices)

---

## Overview

This SOP covers the complete workflow from ordering materials from a vendor to receiving them into inventory at TCS Woodwork.

**System**: AureusERP
**URL**: `/admin`
**Modules Used**: Purchases, Inventories, Invoicing

---

## Part 1: Creating a Purchase Order

### Step 1: Access Purchase Orders

1. Log in to **Admin Panel** (`/admin`)
2. Navigate to: **Purchases** → **Orders** → **Purchase Orders**

### Step 2: Create New Purchase Order

1. Click **"Create"** or **"New Purchase Order"**
2. Fill in the header information:

| Field | Description | Required |
|-------|-------------|----------|
| **Vendor** | Select from existing vendors or create new | Yes |
| **Vendor Reference** | Vendor's quote/reference number | No |
| **Currency** | Defaults to USD | Yes |
| **Payment Terms** | Net 30, Due on Receipt, etc. | No |
| **Planned Date** | Expected delivery date | Recommended |

### Step 3: Add Line Items

1. Click **"Add Line"** in the Products section
2. For each item, fill in:

| Field | Description |
|-------|-------------|
| **Product** | Select from product catalog |
| **Description** | Auto-fills from product, can modify |
| **Quantity** | Number of units to order |
| **Unit of Measure** | Each, Box, Linear Ft, Sheet, etc. |
| **Unit Price** | Auto-fills from vendor pricing if configured |
| **Taxes** | Select applicable taxes |

3. Repeat for all items needed

### Step 4: Review & Confirm

1. Review the totals at the bottom:
   - **Untaxed Amount**
   - **Taxes**
   - **Total**
2. Click **"Confirm Order"** to change status from Draft → Purchase
3. **Optional**: Click **"Send by Email"** to email the PO directly to the vendor

---

## Part 2: Receiving Materials

### Method A: Receive from Purchase Order (Recommended)

This method links the receipt directly to the PO for full traceability.

1. Open the confirmed **Purchase Order**
2. Click the **"Confirm Receipt"** action button (or look for "Receive Products")
3. This automatically creates a **Receipt** operation in Inventories
4. Navigate to: **Inventories** → **Operations** → **Receipts**
5. Find the receipt linked to your PO (look for the PO number in Source Document)
6. Open the receipt and for each line item:
   - Verify or enter **Quantity Received**
   - Confirm **Destination Location** (e.g., `WH/Stock`, `WH/Raw Materials`)
7. Click **"Validate"** to complete the receipt

### Method B: Create Receipt Directly

Use this if you need to receive items without a PO (not recommended for standard workflow).

1. Navigate to: **Inventories** → **Operations** → **Receipts**
2. Click **"Create"**
3. Fill in the header:
   - **Partner**: Select the vendor
   - **Source Document**: Enter the PO number for reference
   - **Destination Location**: Where items will be stored
4. Add product lines with quantities received
5. Click **"Validate"** to complete the receipt

---

## Part 3: Partial Receipts

When you receive only part of an order:

1. Open the **Purchase Order**
2. Click on the **Receipts** tab/relation
3. Open the linked receipt operation
4. Edit the quantities to match what was **actually received**
5. Click **"Validate"** to complete the partial receipt
6. The system will:
   - Update the PO status to **"Partially Received"**
   - Automatically create a **backorder** receipt for the remaining items
7. When the rest arrives, find the backorder receipt and validate it

---

## Part 4: Creating Vendor Bills

After receiving goods, create a bill to track what you owe the vendor:

1. Open the **Purchase Order**
2. Click the **"Create Bill"** action button
3. This generates a vendor bill in the Invoicing module
4. Navigate to: **Invoicing** → **Vendors** → **Bills**
5. Find and open the new bill
6. Verify:
   - Amounts match the vendor's invoice
   - Bill date matches invoice date
   - Due date is correct per payment terms
7. Click **"Confirm"** when ready to post to accounting

---

## Quick Reference

### PO Status Flow

```
┌─────────┐    ┌─────────┐    ┌──────────────────┐    ┌─────────┐
│  Draft  │ →  │  Sent   │ →  │ Purchase (Conf.) │ →  │  Done   │
└─────────┘    └─────────┘    └──────────────────┘    └─────────┘
                                       │
                              ┌────────┴────────┐
                              ▼                 ▼
                       Receipt Status      Invoice Status
                       ─────────────       ──────────────
                       No                  No
                       Pending             Partial
                       Partial             Fully Billed
                       Full
```

### Navigation Paths

| Action | Navigation Path |
|--------|-----------------|
| Create Purchase Order | Admin → Purchases → Orders → Purchase Orders → Create |
| View/Manage Vendors | Admin → Purchases → Orders → Vendors |
| Receive Goods | Admin → Inventories → Operations → Receipts |
| View Vendor Bills | Admin → Invoicing → Vendors → Bills |
| Set Vendor Pricing | Admin → Purchases → Configurations → Vendor Prices |
| View Products | Admin → Purchases → Products |

### Keyboard Shortcuts (FilamentPHP)

| Shortcut | Action |
|----------|--------|
| `Ctrl + S` | Save current form |
| `Esc` | Close modal/cancel |

---

## Tips & Best Practices

### Before Creating POs

- **Set up Vendor Pricing**: Go to **Purchases** → **Configurations** → **Vendor Prices** to pre-configure prices by vendor and product. This saves time and ensures consistent pricing.

- **Verify Vendor Info**: Ensure vendor contact details and payment terms are up to date.

### When Creating POs

- **Use Planned Dates**: Always set expected delivery dates to help track incoming materials.

- **Add Vendor Reference**: Include the vendor's quote number for easy cross-reference.

- **Double-check Quantities**: Verify UoM matches what you're actually ordering (e.g., each vs. box).

### When Receiving

- **Inspect on Arrival**: Check items for damage before validating receipt.

- **Verify Against PO**: Always compare received quantities to the PO before validating.

- **Label with Job ID**: When receiving for a specific project, note the job/project ID for tracking.

- **Report Discrepancies**: If quantities don't match, create a partial receipt and notify purchasing.

### After Receiving

- **Create Bills Promptly**: Generate vendor bills soon after receiving to keep accounts payable current.

- **Store Properly**: Ensure materials are stored in the correct warehouse location as indicated in the system.

---

## Troubleshooting

### Issue: Can't find the Receipt for my PO

**Solution**:
1. Open the Purchase Order
2. Look for a "Receipts" badge/count in the header
3. Click it to see linked receipts
4. Or search in Inventories → Operations → Receipts by the PO number

### Issue: Quantities don't match between PO and Receipt

**Solution**: This is normal for partial receipts. The system tracks:
- `qty_ordered` - What was on the PO
- `qty_received` - What was actually received
- The difference creates a backorder automatically

### Issue: Can't create a bill from PO

**Solution**:
1. Ensure the PO status is "Purchase" (confirmed)
2. Check that items have been received (receipt validated)
3. Verify you have permissions to create bills

---

## Related Documents

- [Vendor Management SOP](./vendor-management.md) *(if exists)*
- [Inventory Management SOP](./inventory-management.md) *(if exists)*
- [Accounts Payable SOP](./accounts-payable.md) *(if exists)*

---

## Revision History

| Date | Version | Author | Changes |
|------|---------|--------|---------|
| 2024-12-24 | 1.0 | Claude AI | Initial creation |

---

*For questions or issues with this process, contact your system administrator.*
