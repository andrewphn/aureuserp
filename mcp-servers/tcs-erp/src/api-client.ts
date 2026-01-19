/**
 * TCS ERP API Client
 *
 * HTTP client for communicating with the TCS ERP Laravel API.
 * Handles authentication, pagination, and error handling.
 */

import { ApiResponse, PaginatedResponse } from './types.js';

export class TcsErpApiClient {
  private baseUrl: string;
  private apiToken: string;

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
  private async request<T>(
    method: string,
    endpoint: string,
    body?: unknown,
    queryParams?: Record<string, string | number | boolean | undefined>
  ): Promise<T> {
    const url = new URL(`/api/v1${endpoint}`, this.baseUrl);

    // Add query parameters
    if (queryParams) {
      Object.entries(queryParams).forEach(([key, value]) => {
        if (value !== undefined) {
          url.searchParams.append(key, String(value));
        }
      });
    }

    const headers: Record<string, string> = {
      'Authorization': `Bearer ${this.apiToken}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    const options: RequestInit = {
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

  async listProjects(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/projects', undefined, filters as Record<string, string>);
  }

  async getProject(id: number, include?: string[]): Promise<ApiResponse<unknown>> {
    const params = include ? { include: include.join(',') } : undefined;
    return this.request('GET', `/projects/${id}`, undefined, params);
  }

  async createProject(data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/projects', data);
  }

  async updateProject(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/projects/${id}`, data);
  }

  async deleteProject(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/projects/${id}`);
  }

  async getProjectTree(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/projects/${id}/tree`);
  }

  async changeProjectStage(id: number, stage: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/projects/${id}/change-stage`, { stage });
  }

  async calculateProject(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/projects/${id}/calculate`);
  }

  // =========================================================================
  // Rooms
  // =========================================================================

  async listRooms(projectId?: number): Promise<PaginatedResponse<unknown>> {
    const endpoint = projectId ? `/projects/${projectId}/rooms` : '/rooms';
    return this.request('GET', endpoint);
  }

  async getRoom(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/rooms/${id}`);
  }

  async createRoom(projectId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/projects/${projectId}/rooms`, data);
  }

  async updateRoom(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/rooms/${id}`, data);
  }

  async deleteRoom(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/rooms/${id}`);
  }

  // =========================================================================
  // Room Locations
  // =========================================================================

  async listLocations(roomId?: number): Promise<PaginatedResponse<unknown>> {
    const endpoint = roomId ? `/rooms/${roomId}/locations` : '/locations';
    return this.request('GET', endpoint);
  }

  async getLocation(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/locations/${id}`);
  }

  async createLocation(roomId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/rooms/${roomId}/locations`, data);
  }

  async updateLocation(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/locations/${id}`, data);
  }

  async deleteLocation(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/locations/${id}`);
  }

  // =========================================================================
  // Cabinet Runs
  // =========================================================================

  async listCabinetRuns(locationId?: number): Promise<PaginatedResponse<unknown>> {
    const endpoint = locationId ? `/locations/${locationId}/cabinet-runs` : '/cabinet-runs';
    return this.request('GET', endpoint);
  }

  async getCabinetRun(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/cabinet-runs/${id}`);
  }

  async createCabinetRun(locationId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/locations/${locationId}/cabinet-runs`, data);
  }

  async updateCabinetRun(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/cabinet-runs/${id}`, data);
  }

  async deleteCabinetRun(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/cabinet-runs/${id}`);
  }

  // =========================================================================
  // Cabinets
  // =========================================================================

  async listCabinets(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/cabinets', undefined, filters as Record<string, string>);
  }

  async getCabinet(id: number, include?: string[]): Promise<ApiResponse<unknown>> {
    const params = include ? { include: include.join(',') } : undefined;
    return this.request('GET', `/cabinets/${id}`, undefined, params);
  }

  async createCabinet(runId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/cabinet-runs/${runId}/cabinets`, data);
  }

  async updateCabinet(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/cabinets/${id}`, data);
  }

  async deleteCabinet(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/cabinets/${id}`);
  }

  // =========================================================================
  // Cabinet Sections
  // =========================================================================

  async listSections(cabinetId?: number): Promise<PaginatedResponse<unknown>> {
    const endpoint = cabinetId ? `/cabinets/${cabinetId}/sections` : '/sections';
    return this.request('GET', endpoint);
  }

  async getSection(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/sections/${id}`);
  }

  async createSection(cabinetId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/cabinets/${cabinetId}/sections`, data);
  }

  async updateSection(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/sections/${id}`, data);
  }

  async deleteSection(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/sections/${id}`);
  }

  // =========================================================================
  // Drawers
  // =========================================================================

  async listDrawers(sectionId?: number, cabinetId?: number): Promise<PaginatedResponse<unknown>> {
    if (sectionId) {
      return this.request('GET', `/sections/${sectionId}/drawers`);
    }
    if (cabinetId) {
      return this.request('GET', `/cabinets/${cabinetId}/drawers`);
    }
    return this.request('GET', '/drawers');
  }

  async getDrawer(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/drawers/${id}`);
  }

  async createDrawer(sectionId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/sections/${sectionId}/drawers`, data);
  }

  async updateDrawer(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/drawers/${id}`, data);
  }

  async deleteDrawer(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/drawers/${id}`);
  }

  // =========================================================================
  // Doors
  // =========================================================================

  async listDoors(sectionId?: number, cabinetId?: number): Promise<PaginatedResponse<unknown>> {
    if (sectionId) {
      return this.request('GET', `/sections/${sectionId}/doors`);
    }
    if (cabinetId) {
      return this.request('GET', `/cabinets/${cabinetId}/doors`);
    }
    return this.request('GET', '/doors');
  }

  async getDoor(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/doors/${id}`);
  }

  async createDoor(sectionId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/sections/${sectionId}/doors`, data);
  }

  async updateDoor(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/doors/${id}`, data);
  }

  async deleteDoor(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/doors/${id}`);
  }

  // =========================================================================
  // Products
  // =========================================================================

  async listProducts(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/products', undefined, filters as Record<string, string>);
  }

  async getProduct(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/products/${id}`);
  }

  async createProduct(data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/products', data);
  }

  async updateProduct(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/products/${id}`, data);
  }

  async deleteProduct(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/products/${id}`);
  }

  async searchProducts(query: string, limit?: number): Promise<PaginatedResponse<unknown>> {
    // Uses the list endpoint with search filter (no dedicated /products/search endpoint)
    return this.request('GET', '/products', undefined, { search: query, per_page: limit || 25 });
  }

  // =========================================================================
  // Partners
  // =========================================================================

  async listPartners(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/partners', undefined, filters as Record<string, string>);
  }

  async getPartner(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/partners/${id}`);
  }

  async createPartner(data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/partners', data);
  }

  async updatePartner(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/partners/${id}`, data);
  }

  async deletePartner(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/partners/${id}`);
  }

  // =========================================================================
  // Employees
  // =========================================================================

  async listEmployees(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/employees', undefined, filters as Record<string, string>);
  }

  async getEmployee(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/employees/${id}`);
  }

  async createEmployee(data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/employees', data);
  }

  async updateEmployee(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/employees/${id}`, data);
  }

  async deleteEmployee(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/employees/${id}`);
  }

  // =========================================================================
  // Tasks
  // =========================================================================

  async listTasks(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/tasks', undefined, filters as Record<string, string>);
  }

  async getTask(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/tasks/${id}`);
  }

  async createTask(data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/tasks', data);
  }

  async updateTask(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/tasks/${id}`, data);
  }

  async deleteTask(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/tasks/${id}`);
  }

  async completeTask(id: number, actualHours?: number, notes?: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/tasks/${id}/complete`, { actual_hours: actualHours, notes });
  }

  // =========================================================================
  // Cabinets - Additional Methods
  // =========================================================================

  async calculateCabinet(id: number): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/cabinets/${id}/calculate`);
  }

  async getCabinetCutList(id: number, format?: string): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/cabinets/${id}/cut-list`, undefined, { format });
  }

  // =========================================================================
  // AI Interpretation (via Claude Code)
  // =========================================================================

  async getInterpretationContext(reviewId: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/extraction/review/${reviewId}/interpretation-context`);
  }

  async saveInterpretation(reviewId: number, interpretation: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/extraction/review/${reviewId}/save-interpretation`, { interpretation });
  }

  // =========================================================================
  // Rhino Integration
  // =========================================================================

  async rhinoGetDocumentInfo(): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/rhino/document/info');
  }

  async rhinoListGroups(filter?: string): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/rhino/document/groups', undefined, { filter });
  }

  async rhinoExtractCabinet(groupName: string, includeDimensions?: boolean): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/rhino/cabinet/extract', {
      group_name: groupName,
      include_dimensions: includeDimensions
    });
  }

  async rhinoExtractAll(projectId: number, includeDimensions?: boolean): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/rhino/cabinet/extract-all', {
      project_id: projectId,
      include_dimensions: includeDimensions
    });
  }

  async rhinoTriggerExtraction(projectId: number, options?: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/rhino/document/scan', { project_id: projectId, options });
  }

  async rhinoGetExtractionStatus(jobId: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/extraction/jobs/${jobId}`);
  }

  async rhinoSyncToRhino(cabinetId: number, createIfMissing?: boolean): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/rhino/sync/push', {
      cabinet_id: cabinetId,
      create_if_missing: createIfMissing
    });
  }

  async rhinoSyncFromRhino(groupName: string, cabinetId?: number, force?: boolean): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/rhino/sync/pull', {
      group_name: groupName,
      cabinet_id: cabinetId,
      force
    });
  }

  async rhinoGetSyncStatus(projectId: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/rhino/sync/status', undefined, { project_id: projectId });
  }

  async rhinoExecuteScript(script: string, timeout?: number): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/rhino/execute-script', { script, timeout });
  }

  // =========================================================================
  // Review Queue
  // =========================================================================

  async listReviews(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>> {
    return this.request('GET', '/extraction/review-queue', undefined, filters as Record<string, string>);
  }

  async getReview(id: number): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/extraction/review/${id}`);
  }

  async approveReview(id: number, corrections?: Record<string, unknown>, notes?: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/extraction/review/${id}/approve`, { corrections, notes });
  }

  async rejectReview(id: number, reason: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/extraction/review/${id}/reject`, { reason });
  }

  // =========================================================================
  // Webhooks
  // =========================================================================

  async listWebhooks(filters?: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/webhooks', undefined, filters as Record<string, string>);
  }

  async createWebhook(data: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/webhooks/subscribe', data);
  }

  async deleteWebhook(id: number): Promise<ApiResponse<unknown>> {
    return this.request('DELETE', `/webhooks/${id}`);
  }

  async testWebhook(id: number, event?: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/webhooks/${id}/test`, { event });
  }

  // =========================================================================
  // Batch Operations
  // =========================================================================

  async batchCreate(
    entityType: string,
    records: Record<string, unknown>[],
    parentId?: number
  ): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/batch/${entityType}`, {
      operation: 'create',
      records,
      parent_id: parentId
    });
  }

  async batchUpdate(
    entityType: string,
    updates: Array<{ id: number; data: Record<string, unknown> }>
  ): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/batch/${entityType}`, { operation: 'update', updates });
  }

  async batchDelete(entityType: string, ids: number[]): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/batch/${entityType}`, { operation: 'delete', ids });
  }
}
