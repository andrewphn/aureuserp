/**
 * TCS ERP API Client
 *
 * HTTP client for communicating with the TCS ERP Laravel API.
 * Handles authentication, pagination, and error handling.
 *
 * Environment variables:
 * - TCS_ERP_BASE_URL: Base URL for the ERP API
 *   - Local: http://aureuserp.test (default)
 *   - Production: https://staging.tcswoodwork.com
 * - TCS_ERP_API_TOKEN: API token for authentication
 */
export class TcsErpApiClient {
    baseUrl;
    apiToken;
    constructor() {
        // Default to local development URL, use env var for production
        this.baseUrl = process.env.TCS_ERP_BASE_URL || 'http://aureuserp.test';
        this.apiToken = process.env.TCS_ERP_API_TOKEN || '';
        console.error(`API Client initialized: ${this.baseUrl}`);
        if (!this.apiToken) {
            console.warn('TCS_ERP_API_TOKEN not set - API calls will fail');
        }
    }
    /**
     * Make an authenticated API request
     */
    async request(method, endpoint, body, queryParams) {
        const url = new URL(`/api/v1${endpoint}`, this.baseUrl);
        // Add query parameters
        if (queryParams) {
            Object.entries(queryParams).forEach(([key, value]) => {
                if (value !== undefined) {
                    url.searchParams.append(key, String(value));
                }
            });
        }
        const headers = {
            'Authorization': `Bearer ${this.apiToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        const options = {
            method,
            headers,
        };
        if (body && method !== 'GET') {
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url.toString(), options);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`API Error ${response.status}: ${errorText}`);
        }
        return response.json();
    }
    // =========================================================================
    // Projects
    // =========================================================================
    async listProjects(filters) {
        return this.request('GET', '/projects', undefined, filters);
    }
    async getProject(id, include) {
        const params = include ? { include: include.join(',') } : undefined;
        return this.request('GET', `/projects/${id}`, undefined, params);
    }
    async createProject(data) {
        return this.request('POST', '/projects', data);
    }
    async updateProject(id, data) {
        return this.request('PUT', `/projects/${id}`, data);
    }
    async deleteProject(id) {
        return this.request('DELETE', `/projects/${id}`);
    }
    async getProjectTree(id) {
        return this.request('GET', `/projects/${id}/tree`);
    }
    async changeProjectStage(id, stage) {
        return this.request('POST', `/projects/${id}/change-stage`, { stage });
    }
    async calculateProject(id) {
        return this.request('GET', `/projects/${id}/calculate`);
    }
    // =========================================================================
    // Rooms
    // =========================================================================
    async listRooms(projectId) {
        const endpoint = projectId ? `/projects/${projectId}/rooms` : '/rooms';
        return this.request('GET', endpoint);
    }
    async getRoom(id) {
        return this.request('GET', `/rooms/${id}`);
    }
    async createRoom(projectId, data) {
        return this.request('POST', `/projects/${projectId}/rooms`, data);
    }
    async updateRoom(id, data) {
        return this.request('PUT', `/rooms/${id}`, data);
    }
    async deleteRoom(id) {
        return this.request('DELETE', `/rooms/${id}`);
    }
    // =========================================================================
    // Room Locations
    // =========================================================================
    async listLocations(roomId) {
        const endpoint = roomId ? `/rooms/${roomId}/locations` : '/locations';
        return this.request('GET', endpoint);
    }
    async getLocation(id) {
        return this.request('GET', `/locations/${id}`);
    }
    async createLocation(roomId, data) {
        return this.request('POST', `/rooms/${roomId}/locations`, data);
    }
    async updateLocation(id, data) {
        return this.request('PUT', `/locations/${id}`, data);
    }
    async deleteLocation(id) {
        return this.request('DELETE', `/locations/${id}`);
    }
    // =========================================================================
    // Cabinet Runs
    // =========================================================================
    async listCabinetRuns(locationId) {
        const endpoint = locationId ? `/locations/${locationId}/cabinet-runs` : '/cabinet-runs';
        return this.request('GET', endpoint);
    }
    async getCabinetRun(id) {
        return this.request('GET', `/cabinet-runs/${id}`);
    }
    async createCabinetRun(locationId, data) {
        return this.request('POST', `/locations/${locationId}/cabinet-runs`, data);
    }
    async updateCabinetRun(id, data) {
        return this.request('PUT', `/cabinet-runs/${id}`, data);
    }
    async deleteCabinetRun(id) {
        return this.request('DELETE', `/cabinet-runs/${id}`);
    }
    // =========================================================================
    // Cabinets
    // =========================================================================
    async listCabinets(filters) {
        return this.request('GET', '/cabinets', undefined, filters);
    }
    async getCabinet(id, include) {
        const params = include ? { include: include.join(',') } : undefined;
        return this.request('GET', `/cabinets/${id}`, undefined, params);
    }
    async createCabinet(runId, data) {
        return this.request('POST', `/cabinet-runs/${runId}/cabinets`, data);
    }
    async updateCabinet(id, data) {
        return this.request('PUT', `/cabinets/${id}`, data);
    }
    async deleteCabinet(id) {
        return this.request('DELETE', `/cabinets/${id}`);
    }
    // =========================================================================
    // Cabinet Sections
    // =========================================================================
    async listSections(cabinetId) {
        const endpoint = cabinetId ? `/cabinets/${cabinetId}/sections` : '/sections';
        return this.request('GET', endpoint);
    }
    async getSection(id) {
        return this.request('GET', `/sections/${id}`);
    }
    async createSection(cabinetId, data) {
        return this.request('POST', `/cabinets/${cabinetId}/sections`, data);
    }
    async updateSection(id, data) {
        return this.request('PUT', `/sections/${id}`, data);
    }
    async deleteSection(id) {
        return this.request('DELETE', `/sections/${id}`);
    }
    // =========================================================================
    // Drawers
    // =========================================================================
    async listDrawers(sectionId, cabinetId) {
        if (sectionId) {
            return this.request('GET', `/sections/${sectionId}/drawers`);
        }
        if (cabinetId) {
            return this.request('GET', `/cabinets/${cabinetId}/drawers`);
        }
        return this.request('GET', '/drawers');
    }
    async getDrawer(id) {
        return this.request('GET', `/drawers/${id}`);
    }
    async createDrawer(sectionId, data) {
        return this.request('POST', `/sections/${sectionId}/drawers`, data);
    }
    async updateDrawer(id, data) {
        return this.request('PUT', `/drawers/${id}`, data);
    }
    async deleteDrawer(id) {
        return this.request('DELETE', `/drawers/${id}`);
    }
    // =========================================================================
    // Doors
    // =========================================================================
    async listDoors(sectionId, cabinetId) {
        if (sectionId) {
            return this.request('GET', `/sections/${sectionId}/doors`);
        }
        if (cabinetId) {
            return this.request('GET', `/cabinets/${cabinetId}/doors`);
        }
        return this.request('GET', '/doors');
    }
    async getDoor(id) {
        return this.request('GET', `/doors/${id}`);
    }
    async createDoor(sectionId, data) {
        return this.request('POST', `/sections/${sectionId}/doors`, data);
    }
    async updateDoor(id, data) {
        return this.request('PUT', `/doors/${id}`, data);
    }
    async deleteDoor(id) {
        return this.request('DELETE', `/doors/${id}`);
    }
    // =========================================================================
    // Products
    // =========================================================================
    async listProducts(filters) {
        return this.request('GET', '/products', undefined, filters);
    }
    async getProduct(id) {
        return this.request('GET', `/products/${id}`);
    }
    async createProduct(data) {
        return this.request('POST', '/products', data);
    }
    async updateProduct(id, data) {
        return this.request('PUT', `/products/${id}`, data);
    }
    async deleteProduct(id) {
        return this.request('DELETE', `/products/${id}`);
    }
    async searchProducts(query, limit) {
        // Uses the list endpoint with search filter (no dedicated /products/search endpoint)
        return this.request('GET', '/products', undefined, { search: query, per_page: limit || 25 });
    }
    // =========================================================================
    // Partners
    // =========================================================================
    async listPartners(filters) {
        return this.request('GET', '/partners', undefined, filters);
    }
    async getPartner(id) {
        return this.request('GET', `/partners/${id}`);
    }
    async createPartner(data) {
        return this.request('POST', '/partners', data);
    }
    async updatePartner(id, data) {
        return this.request('PUT', `/partners/${id}`, data);
    }
    async deletePartner(id) {
        return this.request('DELETE', `/partners/${id}`);
    }
    // =========================================================================
    // Employees
    // =========================================================================
    async listEmployees(filters) {
        return this.request('GET', '/employees', undefined, filters);
    }
    async getEmployee(id) {
        return this.request('GET', `/employees/${id}`);
    }
    async createEmployee(data) {
        return this.request('POST', '/employees', data);
    }
    async updateEmployee(id, data) {
        return this.request('PUT', `/employees/${id}`, data);
    }
    async deleteEmployee(id) {
        return this.request('DELETE', `/employees/${id}`);
    }
    // =========================================================================
    // Tasks
    // =========================================================================
    async listTasks(filters) {
        return this.request('GET', '/tasks', undefined, filters);
    }
    async getTask(id) {
        return this.request('GET', `/tasks/${id}`);
    }
    async createTask(data) {
        return this.request('POST', '/tasks', data);
    }
    async updateTask(id, data) {
        return this.request('PUT', `/tasks/${id}`, data);
    }
    async deleteTask(id) {
        return this.request('DELETE', `/tasks/${id}`);
    }
    async completeTask(id, actualHours, notes) {
        return this.request('POST', `/tasks/${id}/complete`, { actual_hours: actualHours, notes });
    }
    // =========================================================================
    // Cabinets - Additional Methods
    // =========================================================================
    async calculateCabinet(id) {
        return this.request('POST', `/cabinets/${id}/calculate`);
    }
    async getCabinetCutList(id, format) {
        return this.request('GET', `/cabinets/${id}/cut-list`, undefined, { format });
    }
    // =========================================================================
    // AI Interpretation (via Claude Code)
    // =========================================================================
    async getInterpretationContext(reviewId) {
        return this.request('GET', `/extraction/review/${reviewId}/interpretation-context`);
    }
    async saveInterpretation(reviewId, interpretation) {
        return this.request('POST', `/extraction/review/${reviewId}/save-interpretation`, { interpretation });
    }
    // =========================================================================
    // Rhino Integration
    // =========================================================================
    async rhinoGetDocumentInfo() {
        return this.request('GET', '/rhino/document/info');
    }
    async rhinoListGroups(filter) {
        return this.request('GET', '/rhino/document/groups', undefined, { filter });
    }
    async rhinoExtractCabinet(groupName, includeDimensions) {
        return this.request('POST', '/rhino/cabinet/extract', {
            group_name: groupName,
            include_dimensions: includeDimensions
        });
    }
    async rhinoExtractAll(projectId, includeDimensions) {
        return this.request('POST', '/rhino/cabinet/extract-all', {
            project_id: projectId,
            include_dimensions: includeDimensions
        });
    }
    async rhinoTriggerExtraction(projectId, options) {
        return this.request('POST', '/rhino/document/scan', { project_id: projectId, options });
    }
    async rhinoGetExtractionStatus(jobId) {
        return this.request('GET', `/extraction/jobs/${jobId}`);
    }
    async rhinoSyncToRhino(cabinetId, createIfMissing) {
        return this.request('POST', '/rhino/sync/push', {
            cabinet_id: cabinetId,
            create_if_missing: createIfMissing
        });
    }
    async rhinoSyncFromRhino(groupName, cabinetId, force) {
        return this.request('POST', '/rhino/sync/pull', {
            group_name: groupName,
            cabinet_id: cabinetId,
            force
        });
    }
    async rhinoGetSyncStatus(projectId) {
        return this.request('GET', '/rhino/sync/status', undefined, { project_id: projectId });
    }
    async rhinoExecuteScript(script, timeout) {
        return this.request('POST', '/rhino/execute-script', { script, timeout });
    }
    // =========================================================================
    // Review Queue
    // =========================================================================
    async listReviews(filters) {
        return this.request('GET', '/extraction/review-queue', undefined, filters);
    }
    async getReview(id) {
        return this.request('GET', `/extraction/review/${id}`);
    }
    async approveReview(id, corrections, notes) {
        return this.request('POST', `/extraction/review/${id}/approve`, { corrections, notes });
    }
    async rejectReview(id, reason) {
        return this.request('POST', `/extraction/review/${id}/reject`, { reason });
    }
    // =========================================================================
    // Webhooks
    // =========================================================================
    async listWebhooks(filters) {
        return this.request('GET', '/webhooks', undefined, filters);
    }
    async createWebhook(data) {
        return this.request('POST', '/webhooks/subscribe', data);
    }
    async deleteWebhook(id) {
        return this.request('DELETE', `/webhooks/${id}`);
    }
    async testWebhook(id, event) {
        return this.request('POST', `/webhooks/${id}/test`, { event });
    }
    // =========================================================================
    // Batch Operations
    // =========================================================================
    async batchCreate(entityType, records, parentId) {
        return this.request('POST', `/batch/${entityType}`, {
            operation: 'create',
            records,
            parent_id: parentId
        });
    }
    async batchUpdate(entityType, updates) {
        return this.request('POST', `/batch/${entityType}`, { operation: 'update', updates });
    }
    async batchDelete(entityType, ids) {
        return this.request('POST', `/batch/${entityType}`, { operation: 'delete', ids });
    }
    // =========================================================================
    // Sales Orders
    // =========================================================================
    async listSalesOrders(filters) {
        return this.request('GET', '/sales-orders', undefined, filters);
    }
    async getSalesOrder(id, include) {
        const params = include ? { include: include.join(',') } : undefined;
        return this.request('GET', `/sales-orders/${id}`, undefined, params);
    }
    async createSalesOrder(data) {
        return this.request('POST', '/sales-orders', data);
    }
    async updateSalesOrder(id, data) {
        return this.request('PUT', `/sales-orders/${id}`, data);
    }
    async deleteSalesOrder(id) {
        return this.request('DELETE', `/sales-orders/${id}`);
    }
    async confirmSalesOrder(id) {
        return this.request('POST', `/sales-orders/${id}/confirm`);
    }
    async cancelSalesOrder(id, reason) {
        return this.request('POST', `/sales-orders/${id}/cancel`, { reason });
    }
    async createSalesOrderInvoice(id, data) {
        return this.request('POST', `/sales-orders/${id}/invoice`, data);
    }
    async sendSalesOrderEmail(id, data) {
        return this.request('POST', `/sales-orders/${id}/send-email`, data);
    }
    // Sales Order Lines
    async listSalesOrderLines(orderId) {
        return this.request('GET', `/sales-orders/${orderId}/lines`);
    }
    async createSalesOrderLine(orderId, data) {
        return this.request('POST', `/sales-orders/${orderId}/lines`, data);
    }
    async updateSalesOrderLine(id, data) {
        return this.request('PUT', `/sales-order-lines/${id}`, data);
    }
    async deleteSalesOrderLine(id) {
        return this.request('DELETE', `/sales-order-lines/${id}`);
    }
    // =========================================================================
    // Purchase Orders
    // =========================================================================
    async listPurchaseOrders(filters) {
        return this.request('GET', '/purchase-orders', undefined, filters);
    }
    async getPurchaseOrder(id, include) {
        const params = include ? { include: include.join(',') } : undefined;
        return this.request('GET', `/purchase-orders/${id}`, undefined, params);
    }
    async createPurchaseOrder(data) {
        return this.request('POST', '/purchase-orders', data);
    }
    async updatePurchaseOrder(id, data) {
        return this.request('PUT', `/purchase-orders/${id}`, data);
    }
    async deletePurchaseOrder(id) {
        return this.request('DELETE', `/purchase-orders/${id}`);
    }
    async confirmPurchaseOrder(id) {
        return this.request('POST', `/purchase-orders/${id}/confirm`);
    }
    async cancelPurchaseOrder(id, reason) {
        return this.request('POST', `/purchase-orders/${id}/cancel`, { reason });
    }
    async createPurchaseOrderBill(id, data) {
        return this.request('POST', `/purchase-orders/${id}/create-bill`, data);
    }
    async sendPurchaseOrderEmail(id, data) {
        return this.request('POST', `/purchase-orders/${id}/send-email`, data);
    }
    // Purchase Order Lines
    async listPurchaseOrderLines(orderId) {
        return this.request('GET', `/purchase-orders/${orderId}/lines`);
    }
    async createPurchaseOrderLine(orderId, data) {
        return this.request('POST', `/purchase-orders/${orderId}/lines`, data);
    }
    async updatePurchaseOrderLine(id, data) {
        return this.request('PUT', `/purchase-order-lines/${id}`, data);
    }
    async deletePurchaseOrderLine(id) {
        return this.request('DELETE', `/purchase-order-lines/${id}`);
    }
    // =========================================================================
    // Invoices
    // =========================================================================
    async listInvoices(filters) {
        return this.request('GET', '/invoices', undefined, filters);
    }
    async getInvoice(id, include) {
        const params = include ? { include: include.join(',') } : undefined;
        return this.request('GET', `/invoices/${id}`, undefined, params);
    }
    async createInvoice(data) {
        return this.request('POST', '/invoices', data);
    }
    async updateInvoice(id, data) {
        return this.request('PUT', `/invoices/${id}`, data);
    }
    async deleteInvoice(id) {
        return this.request('DELETE', `/invoices/${id}`);
    }
    async postInvoice(id) {
        return this.request('POST', `/invoices/${id}/post`);
    }
    async payInvoice(id, data) {
        return this.request('POST', `/invoices/${id}/pay`, data);
    }
    async createInvoiceCreditNote(id, data) {
        return this.request('POST', `/invoices/${id}/credit-note`, data);
    }
    async resetInvoiceToDraft(id) {
        return this.request('POST', `/invoices/${id}/reset-draft`);
    }
    async sendInvoiceEmail(id, data) {
        return this.request('POST', `/invoices/${id}/send-email`, data);
    }
    // =========================================================================
    // Bills
    // =========================================================================
    async listBills(filters) {
        return this.request('GET', '/bills', undefined, filters);
    }
    async getBill(id, include) {
        const params = include ? { include: include.join(',') } : undefined;
        return this.request('GET', `/bills/${id}`, undefined, params);
    }
    async createBill(data) {
        return this.request('POST', '/bills', data);
    }
    async updateBill(id, data) {
        return this.request('PUT', `/bills/${id}`, data);
    }
    async deleteBill(id) {
        return this.request('DELETE', `/bills/${id}`);
    }
    async postBill(id) {
        return this.request('POST', `/bills/${id}/post`);
    }
    async payBill(id, data) {
        return this.request('POST', `/bills/${id}/pay`, data);
    }
    async resetBillToDraft(id) {
        return this.request('POST', `/bills/${id}/reset-draft`);
    }
    // =========================================================================
    // Payments
    // =========================================================================
    async listPayments(filters) {
        return this.request('GET', '/payments', undefined, filters);
    }
    async getPayment(id) {
        return this.request('GET', `/payments/${id}`);
    }
    async createPayment(data) {
        return this.request('POST', '/payments', data);
    }
    async updatePayment(id, data) {
        return this.request('PUT', `/payments/${id}`, data);
    }
    async deletePayment(id) {
        return this.request('DELETE', `/payments/${id}`);
    }
    async postPayment(id) {
        return this.request('POST', `/payments/${id}/post`);
    }
    async cancelPayment(id, reason) {
        return this.request('POST', `/payments/${id}/cancel`, { reason });
    }
    async registerPayment(data) {
        return this.request('POST', '/payments/register', data);
    }
    // =========================================================================
    // Calculators
    // =========================================================================
    async calculateCabinetDimensions(data) {
        return this.request('POST', '/calculators/cabinet', data);
    }
    async calculateDrawerDimensions(data) {
        return this.request('POST', '/calculators/drawer', data);
    }
    async calculateStretcherDimensions(data) {
        return this.request('POST', '/calculators/stretcher', data);
    }
    // =========================================================================
    // Bill of Materials (BOM)
    // =========================================================================
    async listBom(filters) {
        return this.request('GET', '/bom', undefined, filters);
    }
    async getBom(id) {
        return this.request('GET', `/bom/${id}`);
    }
    async createBom(data) {
        return this.request('POST', '/bom', data);
    }
    async updateBom(id, data) {
        return this.request('PUT', `/bom/${id}`, data);
    }
    async deleteBom(id) {
        return this.request('DELETE', `/bom/${id}`);
    }
    async getBomByProject(projectId) {
        return this.request('GET', `/bom/by-project/${projectId}`);
    }
    async getBomByCabinet(cabinetId) {
        return this.request('GET', `/bom/by-cabinet/${cabinetId}`);
    }
    async generateBom(projectId, overwrite) {
        return this.request('POST', `/bom/generate/${projectId}`, { overwrite });
    }
    async bulkUpdateBomStatus(ids, status) {
        return this.request('POST', '/bom/bulk-update-status', { ids, status });
    }
    // =========================================================================
    // Stock (Product Quantities)
    // =========================================================================
    async listStock(filters) {
        return this.request('GET', '/stock', undefined, filters);
    }
    async getStock(id) {
        return this.request('GET', `/stock/${id}`);
    }
    async getStockByProduct(productId) {
        return this.request('GET', `/stock/by-product/${productId}`);
    }
    async getStockByLocation(locationId) {
        return this.request('GET', `/stock/by-location/${locationId}`);
    }
    async adjustStock(data) {
        return this.request('POST', '/stock/adjust', data);
    }
    async transferStock(data) {
        return this.request('POST', '/stock/transfer', data);
    }
    // =========================================================================
    // Product Categories
    // =========================================================================
    async listProductCategories(filters) {
        return this.request('GET', '/product-categories', undefined, filters);
    }
    async getProductCategoriesTree() {
        return this.request('GET', '/product-categories/tree');
    }
    async getProductCategory(id) {
        return this.request('GET', `/product-categories/${id}`);
    }
    async createProductCategory(data) {
        return this.request('POST', '/product-categories', data);
    }
    async updateProductCategory(id, data) {
        return this.request('PUT', `/product-categories/${id}`, data);
    }
    async deleteProductCategory(id) {
        return this.request('DELETE', `/product-categories/${id}`);
    }
    // =========================================================================
    // Change Orders
    // =========================================================================
    async listChangeOrders(filters) {
        return this.request('GET', '/change-orders', undefined, filters);
    }
    async getChangeOrder(id) {
        return this.request('GET', `/change-orders/${id}`);
    }
    async createChangeOrder(data) {
        return this.request('POST', '/change-orders', data);
    }
    async updateChangeOrder(id, data) {
        return this.request('PUT', `/change-orders/${id}`, data);
    }
    async deleteChangeOrder(id) {
        return this.request('DELETE', `/change-orders/${id}`);
    }
    async approveChangeOrder(id, data) {
        return this.request('POST', `/change-orders/${id}/approve`, data);
    }
    async rejectChangeOrder(id, reason) {
        return this.request('POST', `/change-orders/${id}/reject`, { reason });
    }
    async getChangeOrdersByProject(projectId) {
        return this.request('GET', `/change-orders/by-project/${projectId}`);
    }
    // =========================================================================
    // Project Workflow Actions
    // =========================================================================
    async cloneProject(id, data) {
        return this.request('POST', `/projects/${id}/clone`, data);
    }
    async getProjectGateStatus(id) {
        return this.request('GET', `/projects/${id}/gate-status`);
    }
    async getProjectBom(id) {
        return this.request('GET', `/projects/${id}/bom`);
    }
    async generateProjectOrder(id, data) {
        return this.request('POST', `/projects/${id}/generate-order`, data);
    }
}
