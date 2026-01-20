# n8n Slack Chatbot Workflow - Complete Test Plan
## TCS ERP - Gemini Slack Chatbot Workflow

**Date:** 2025-01-20  
**Workflow ID:** P9EIYMMb5Up3Q67STmFaM  
**Status:** Ready for Testing  
**Version:** 2.0 (Consolidated)

---

## Overview

This comprehensive test plan validates that the enhanced Slack chatbot workflow correctly uses all 187 specific API tools instead of the generic HTTP Request tool. The workflow should intelligently select the appropriate tool based on user queries while ensuring **read-only operations** during testing to prevent any data modifications.

**Key Features:**
- 187 specific API tool nodes (replacing single generic HTTP Request tool)
- AI Agent with enhanced tool selection guidance
- Read-only verification procedures
- Comprehensive test scenarios across 6 categories
- Slack MCP integration for automated testing

---

## Section 1: Pre-Test Verification

### 1.1 Workflow Configuration Checklist

- [x] 187 tool nodes created and added to workflow
- [x] Old generic HTTP Request tool (`a2f917a3-7d9b-471e-95b1-1b22ef433eb5`) removed
- [x] All tools connected to AI Agent node (`caec279f-5f17-43bc-9db4-144913d9cf31`)
- [x] AI Agent system message updated with tool selection guidelines
- [x] Workflow is active and accessible
- [ ] Read-only mode verified (see Section 2)

### 1.2 Test Environment

- **n8n Instance:** https://n8n.tcswoodwork.com
- **Slack Integration:** Configured and active
- **Slack MCP:** Available for automated test messaging
- **ERP API:** https://staging.tcswoodwork.com/api/v1
- **Authentication:** Bearer token via environment variable

### 1.3 Read-Only Mode Verification

**Critical:** All tests must use read-only operations only. No POST/PUT/DELETE operations allowed during testing.

**Pre-Test Checks:**
1. Review AI Agent system message to ensure it instructs using GET/list/search tools only
2. Verify no POST/PUT/DELETE tools are called during testing
3. Set up API monitoring to track all calls
4. Document current database record counts for key tables:
   - `products` table count: ______
   - `stock` table count: ______
   - `projects` table count: ______

**Allowed Tool Types for Testing:**
- ✅ `list_*` tools (GET operations)
- ✅ `get_*` tools (GET operations)
- ✅ `search_*` tools (GET operations)
- ❌ `create_*` tools (POST operations) - **BLOCKED**
- ❌ `update_*` tools (PUT operations) - **BLOCKED**
- ❌ `delete_*` tools (DELETE operations) - **BLOCKED**

---

## Section 2: Read-Only Verification Tests

### Test 2.1: Verify Read-Only Tool Usage

**Objective:** Ensure workflow only uses GET/list/search operations during testing

**Test Steps:**
1. Monitor n8n execution logs for all tool calls
2. Verify only GET/list/search operations are used
3. Track API calls to staging.tcswoodwork.com
4. Log any unexpected POST/PUT/DELETE calls

**Success Criteria:**
- ✅ Zero POST/PUT/DELETE operations during testing
- ✅ All tool calls are GET/list/search operations
- ✅ API monitoring confirms read-only access

### Test 2.2: Database State Verification

**Objective:** Verify no data modifications occur during testing

**Test Steps:**
1. Document database record counts before testing
2. Execute all test scenarios
3. Compare database record counts after testing

**Success Criteria:**
- ✅ Database record counts unchanged
- ✅ No new records created
- ✅ No existing records modified
- ✅ No records deleted

### Test 2.3: API Call Logging

**Objective:** Monitor and log all API calls to ensure read-only operations

**Test Steps:**
1. Enable detailed API logging
2. Capture all HTTP requests during test execution
3. Verify all requests use GET method
4. Document any unexpected operations

**Success Criteria:**
- ✅ All API calls logged successfully
- ✅ 100% of calls use GET method
- ✅ No write operations detected

---

## Section 3: Tool Selection Tests

### Test Category 1: Product & Inventory Queries

#### Test 1.1: Product Search by Name
**Query:** "How many drawer slides do we have?"

**Expected Behavior:**
1. AI uses `search_products` tool with query="drawer slides"
2. AI uses `get_stock_by_product` tool with the product_id from search results
3. Returns total quantity across all locations

**Success Criteria:**
- ✅ Uses `search_products` tool (not generic HTTP Request)
- ✅ Uses `get_stock_by_product` tool
- ✅ Returns accurate inventory count
- ✅ Response formatted for Slack
- ✅ Read-only operations only

**Test Steps:**
1. Send message via Slack MCP: "How many drawer slides do we have?"
2. Monitor n8n workflow execution
3. Verify tool calls in execution log
4. Verify response in Slack
5. Confirm no data modifications

---

#### Test 1.2: Product Search with Filters
**Query:** "Show me all products from vendor X"

**Expected Behavior:**
1. AI uses `list_products` tool with `vendor_id` filter
2. Returns filtered product list

**Success Criteria:**
- ✅ Uses `list_products` tool with vendor_id parameter
- ✅ Returns relevant products only
- ✅ Response includes product details
- ✅ Read-only operations only

---

#### Test 1.3: Get Specific Product Details
**Query:** "What are the details for product SKU ABC-123?"

**Expected Behavior:**
1. AI uses `search_products` or `list_products` to find product by SKU
2. AI uses `get_product` tool with product ID
3. Returns full product details including inventory

**Success Criteria:**
- ✅ Uses `get_product` tool
- ✅ Returns complete product information
- ✅ Includes inventory/stock data if available
- ✅ Read-only operations only

---

### Test Category 2: Stock & Inventory Management

#### Test 2.1: Stock Query by Product
**Query:** "What's our inventory of plywood?"

**Expected Behavior:**
1. AI uses `search_products` to find "plywood"
2. AI uses `get_stock_by_product` with product_id
3. Returns stock quantities by location

**Success Criteria:**
- ✅ Uses stock-specific tools
- ✅ Returns location-based inventory breakdown
- ✅ Shows total quantity
- ✅ Read-only operations only

---

#### Test 2.2: Stock Query by Location
**Query:** "What products do we have in the main warehouse?"

**Expected Behavior:**
1. AI uses `list_locations` to find "main warehouse"
2. AI uses `get_stock_by_location` or `list_stock` with location filter
3. Returns products and quantities at that location

**Success Criteria:**
- ✅ Uses location-specific stock tools
- ✅ Returns products for specified location
- ✅ Includes quantities
- ✅ Read-only operations only

---

#### Test 2.3: Low Stock Alert
**Query:** "Show me products that are low on stock"

**Expected Behavior:**
1. AI uses `list_stock` tool with `low_stock: true` filter
2. Returns products below minimum quantity

**Success Criteria:**
- ✅ Uses `list_stock` with low_stock filter
- ✅ Returns only low-stock items
- ✅ Includes current vs minimum quantities
- ✅ Read-only operations only

---

### Test Category 3: Project Queries

#### Test 3.1: Get Project Details
**Query:** "Show me project ABC-123"

**Expected Behavior:**
1. AI uses `list_projects` to find project by name/ID
2. AI uses `get_project` tool with project ID
3. Returns project details

**Success Criteria:**
- ✅ Uses `get_project` tool
- ✅ Returns complete project information
- ✅ Includes project status, stages, etc.
- ✅ Read-only operations only

---

#### Test 3.2: Get Project Tree
**Query:** "Show me the full structure of project ABC-123"

**Expected Behavior:**
1. AI uses `get_project` to get project ID
2. AI uses `get_project_tree` tool
3. Returns hierarchical project structure (rooms, cabinets, etc.)

**Success Criteria:**
- ✅ Uses `get_project_tree` tool
- ✅ Returns hierarchical structure
- ✅ Includes all project components
- ✅ Read-only operations only

---

#### Test 3.3: List Projects with Filters
**Query:** "Show me all active projects"

**Expected Behavior:**
1. AI uses `list_projects` tool with appropriate filters
2. Returns filtered project list

**Success Criteria:**
- ✅ Uses `list_projects` with filters
- ✅ Returns only active projects
- ✅ Response is properly formatted
- ✅ Read-only operations only

---

### Test Category 4: Multi-Tool Queries

#### Test 4.1: Complex Inventory Query
**Query:** "How many drawer slides do we have, and what projects are using them?"

**Expected Behavior:**
1. AI uses `search_products` to find "drawer slides"
2. AI uses `get_stock_by_product` for inventory
3. AI uses `list_projects` and potentially `get_project_bom` to find usage
4. Combines results into comprehensive answer

**Success Criteria:**
- ✅ Uses multiple specific tools
- ✅ Combines data from different sources
- ✅ Provides comprehensive answer
- ✅ Read-only operations only

---

#### Test 4.2: Product and Vendor Information
**Query:** "Show me all products from vendor X and their current stock levels"

**Expected Behavior:**
1. AI uses `list_products` with vendor_id filter
2. For each product, uses `get_stock_by_product`
3. Combines product and stock data

**Success Criteria:**
- ✅ Uses product and stock tools together
- ✅ Returns combined product + inventory data
- ✅ Response is well-organized
- ✅ Read-only operations only

---

### Test Category 5: Error Handling

#### Test 5.1: Invalid Product Query
**Query:** "How many unicorn horns do we have?"

**Expected Behavior:**
1. AI uses `search_products` tool
2. No results found
3. Returns helpful message: "No products found matching 'unicorn horns'"

**Success Criteria:**
- ✅ Handles empty results gracefully
- ✅ Provides helpful error message
- ✅ Doesn't crash or return generic errors
- ✅ Read-only operations only

---

#### Test 5.2: Missing Required Parameters
**Query:** "Get product details"

**Expected Behavior:**
1. AI recognizes missing product identifier
2. Asks user for clarification: "Which product would you like details for? Please provide a product name, SKU, or ID."

**Success Criteria:**
- ✅ Recognizes missing information
- ✅ Asks for clarification
- ✅ Doesn't attempt invalid API calls
- ✅ Read-only operations only

---

### Test Category 6: Tool Selection Accuracy

#### Test 6.1: Verify Correct Tool Usage
**Test:** Monitor workflow executions and verify AI selects the most specific tool available

**Queries to Test:**
- "List products" → Should use `list_products` (not generic HTTP)
- "Get product 123" → Should use `get_product` (not `list_products`)
- "Search for slides" → Should use `search_products` (not `list_products`)
- "Stock for product 123" → Should use `get_stock_by_product` (not `list_stock`)

**Success Criteria:**
- ✅ AI always selects the most specific tool
- ✅ No generic HTTP Request tool calls
- ✅ Tool parameters are correctly populated
- ✅ Read-only operations only

---

## Section 4: Execution Procedures

### 4.1 Test Execution Order

1. **Read-only verification tests first** (ensure safety)
2. **Simple queries** (single tool usage)
3. **Complex queries** (multi-tool usage)
4. **Error handling** (edge cases)
5. **Tool selection accuracy** (verify correct tool usage)

### 4.2 Test Data Requirements

- Use existing production/staging data only
- No test data creation required
- All queries should use real product names, project IDs, etc.
- Document which real data is used for each test

### 4.3 Monitoring & Logging

- Use n8n execution history to verify tool calls
- Capture Slack responses for verification
- Document any deviations from expected behavior
- Log response times and accuracy
- Monitor API calls to ensure read-only operations

### 4.4 Using Slack MCP for Testing

**Automated Testing via Slack MCP:**
1. Use Slack MCP `send_message` tool to send test queries
2. Monitor n8n workflow execution in real-time
3. Capture responses via Slack MCP
4. Document results automatically

**Example Test Execution:**
```javascript
// Send test query via Slack MCP
send_message({
  recipient: "#n8n-test-channel",
  message: "How many drawer slides do we have?"
});

// Monitor workflow execution
// Verify tool calls
// Capture response
```

---

## Section 5: Success Metrics

### Primary Metrics

- **Tool Selection Accuracy:** 95%+ of queries use the correct specific tool
- **Response Accuracy:** 90%+ of responses provide correct information
- **Error Rate:** <5% of queries result in errors
- **Response Time:** Average response time <10 seconds
- **Read-Only Compliance:** 100% of test operations are read-only

### Secondary Metrics

- **User Satisfaction:** Responses are helpful and well-formatted
- **Tool Coverage:** All major tool categories are utilized
- **Error Handling:** Graceful handling of edge cases
- **Data Safety:** Zero data modifications during testing

---

## Section 6: Test Results Log

| Test ID | Query | Expected Tool(s) | Actual Tool(s) | Status | Notes |
|---------|-------|-----------------|----------------|--------|-------|
| 2.1 | Read-only verification | GET/list/search only | TBD | ⏳ Pending | |
| 2.2 | Database state check | N/A | TBD | ⏳ Pending | |
| 2.3 | API call logging | GET only | TBD | ⏳ Pending | |
| 1.1 | "How many drawer slides do we have?" | search_products, get_stock_by_product | TBD | ⏳ Pending | |
| 1.2 | "Show me all products from vendor X" | list_products | TBD | ⏳ Pending | |
| 1.3 | "What are the details for product SKU ABC-123?" | search_products, get_product | TBD | ⏳ Pending | |
| 2.1 | "What's our inventory of plywood?" | search_products, get_stock_by_product | TBD | ⏳ Pending | |
| 2.2 | "What products do we have in the main warehouse?" | list_locations, get_stock_by_location | TBD | ⏳ Pending | |
| 2.3 | "Show me products that are low on stock" | list_stock | TBD | ⏳ Pending | |
| 3.1 | "Show me project ABC-123" | list_projects, get_project | TBD | ⏳ Pending | |
| 3.2 | "Show me the full structure of project ABC-123" | get_project, get_project_tree | TBD | ⏳ Pending | |
| 3.3 | "Show me all active projects" | list_projects | TBD | ⏳ Pending | |
| 4.1 | "How many drawer slides do we have, and what projects are using them?" | search_products, get_stock_by_product, list_projects | TBD | ⏳ Pending | |
| 4.2 | "Show me all products from vendor X and their current stock levels" | list_products, get_stock_by_product | TBD | ⏳ Pending | |
| 5.1 | "How many unicorn horns do we have?" | search_products | TBD | ⏳ Pending | |
| 5.2 | "Get product details" | (should ask for clarification) | TBD | ⏳ Pending | |
| 6.1 | Tool selection accuracy | Various specific tools | TBD | ⏳ Pending | |

---

## Section 7: Regression Tests

### Quick Smoke Tests (Run after any changes)

1. "How many drawer slides do we have?" → Should work
2. "Show me project ABC-123" → Should work
3. "List all products" → Should work
4. "What's in stock?" → Should work

**All smoke tests must:**
- ✅ Use specific tools (not generic HTTP Request)
- ✅ Return accurate results
- ✅ Complete in <10 seconds
- ✅ Use read-only operations only

---

## Section 8: Known Issues & Limitations

### Current Limitations

- Some tools may require additional parameter validation
- Complex multi-step queries may need refinement
- Error messages could be more user-friendly

### Future Enhancements

- Add more descriptive tool descriptions
- Improve parameter extraction from natural language
- Add support for more complex queries
- Enhance read-only mode enforcement

---

## Section 9: Post-Test Verification

### 9.1 Database State Verification

- Compare database record counts (should be unchanged)
- Review API call logs (should only show GET requests)
- Verify no new records created in ERP system
- Confirm no data modifications occurred

### 9.2 Cleanup Verification

- Verify all temporary test files cleaned up
- Confirm no test data left in system
- Document cleanup actions taken

---

## Next Steps

1. **Execute Test Scenarios:** Run through all test cases systematically
2. **Document Results:** Log actual behavior for each test
3. **Fix Issues:** Address any problems found during testing
4. **Re-test:** Verify fixes with regression tests
5. **Deploy:** Once all tests pass, deploy to production

---

## Contact & Support

- **Workflow Owner:** TCS Woodwork Team
- **n8n Instance:** https://n8n.tcswoodwork.com
- **Documentation:** See workflow notes in n8n

---

**Last Updated:** 2025-01-20  
**Version:** 2.0 (Consolidated)
