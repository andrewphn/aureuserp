/**
 * TCS ERP API Client
 *
 * HTTP client for communicating with the TCS ERP Laravel API.
 * Handles authentication, pagination, and error handling.
 */
export class TcsErpApiClient {
    baseUrl;
    apiToken;
    constructor() {
        this.baseUrl = process.env.TCS_ERP_BASE_URL || 'https://staging.tcswoodwork.com';
        this.apiToken = process.env.TCS_ERP_API_TOKEN || '';
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
}
