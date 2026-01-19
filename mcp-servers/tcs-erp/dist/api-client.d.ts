/**
 * TCS ERP API Client
 *
 * HTTP client for communicating with the TCS ERP Laravel API.
 * Handles authentication, pagination, and error handling.
 */
import { ApiResponse, PaginatedResponse } from './types.js';
export declare class TcsErpApiClient {
    private baseUrl;
    private apiToken;
    constructor();
    /**
     * Make an authenticated API request
     */
    private request;
    listProjects(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getProject(id: number, include?: string[]): Promise<ApiResponse<unknown>>;
    createProject(data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateProject(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteProject(id: number): Promise<ApiResponse<unknown>>;
    listRooms(projectId?: number): Promise<PaginatedResponse<unknown>>;
    getRoom(id: number): Promise<ApiResponse<unknown>>;
    createRoom(projectId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateRoom(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteRoom(id: number): Promise<ApiResponse<unknown>>;
    listLocations(roomId?: number): Promise<PaginatedResponse<unknown>>;
    getLocation(id: number): Promise<ApiResponse<unknown>>;
    createLocation(roomId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateLocation(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteLocation(id: number): Promise<ApiResponse<unknown>>;
    listCabinetRuns(locationId?: number): Promise<PaginatedResponse<unknown>>;
    getCabinetRun(id: number): Promise<ApiResponse<unknown>>;
    createCabinetRun(locationId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateCabinetRun(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteCabinetRun(id: number): Promise<ApiResponse<unknown>>;
    listCabinets(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getCabinet(id: number, include?: string[]): Promise<ApiResponse<unknown>>;
    createCabinet(runId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateCabinet(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteCabinet(id: number): Promise<ApiResponse<unknown>>;
    listSections(cabinetId?: number): Promise<PaginatedResponse<unknown>>;
    getSection(id: number): Promise<ApiResponse<unknown>>;
    createSection(cabinetId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateSection(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteSection(id: number): Promise<ApiResponse<unknown>>;
    listDrawers(sectionId?: number, cabinetId?: number): Promise<PaginatedResponse<unknown>>;
    getDrawer(id: number): Promise<ApiResponse<unknown>>;
    createDrawer(sectionId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateDrawer(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteDrawer(id: number): Promise<ApiResponse<unknown>>;
    listDoors(sectionId?: number, cabinetId?: number): Promise<PaginatedResponse<unknown>>;
    getDoor(id: number): Promise<ApiResponse<unknown>>;
    createDoor(sectionId: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateDoor(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteDoor(id: number): Promise<ApiResponse<unknown>>;
    listProducts(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getProduct(id: number): Promise<ApiResponse<unknown>>;
    createProduct(data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateProduct(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteProduct(id: number): Promise<ApiResponse<unknown>>;
    searchProducts(query: string, limit?: number): Promise<PaginatedResponse<unknown>>;
    listPartners(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getPartner(id: number): Promise<ApiResponse<unknown>>;
    createPartner(data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updatePartner(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deletePartner(id: number): Promise<ApiResponse<unknown>>;
    listEmployees(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getEmployee(id: number): Promise<ApiResponse<unknown>>;
    createEmployee(data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateEmployee(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteEmployee(id: number): Promise<ApiResponse<unknown>>;
    listTasks(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getTask(id: number): Promise<ApiResponse<unknown>>;
    createTask(data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    updateTask(id: number, data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteTask(id: number): Promise<ApiResponse<unknown>>;
    completeTask(id: number, actualHours?: number, notes?: string): Promise<ApiResponse<unknown>>;
    calculateCabinet(id: number): Promise<ApiResponse<unknown>>;
    getCabinetCutList(id: number, format?: string): Promise<ApiResponse<unknown>>;
    getInterpretationContext(reviewId: number): Promise<ApiResponse<unknown>>;
    saveInterpretation(reviewId: number, interpretation: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    rhinoGetDocumentInfo(): Promise<ApiResponse<unknown>>;
    rhinoListGroups(filter?: string): Promise<ApiResponse<unknown>>;
    rhinoExtractCabinet(groupName: string, includeDimensions?: boolean): Promise<ApiResponse<unknown>>;
    rhinoExtractAll(projectId: number, includeDimensions?: boolean): Promise<ApiResponse<unknown>>;
    rhinoTriggerExtraction(projectId: number, options?: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    rhinoGetExtractionStatus(jobId: number): Promise<ApiResponse<unknown>>;
    rhinoSyncToRhino(cabinetId: number, createIfMissing?: boolean): Promise<ApiResponse<unknown>>;
    rhinoSyncFromRhino(groupName: string, cabinetId?: number, force?: boolean): Promise<ApiResponse<unknown>>;
    rhinoGetSyncStatus(projectId: number): Promise<ApiResponse<unknown>>;
    rhinoExecuteScript(script: string, timeout?: number): Promise<ApiResponse<unknown>>;
    listReviews(filters?: Record<string, unknown>): Promise<PaginatedResponse<unknown>>;
    getReview(id: number): Promise<ApiResponse<unknown>>;
    approveReview(id: number, corrections?: Record<string, unknown>, notes?: string): Promise<ApiResponse<unknown>>;
    rejectReview(id: number, reason: string): Promise<ApiResponse<unknown>>;
    listWebhooks(filters?: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    createWebhook(data: Record<string, unknown>): Promise<ApiResponse<unknown>>;
    deleteWebhook(id: number): Promise<ApiResponse<unknown>>;
    testWebhook(id: number, event?: string): Promise<ApiResponse<unknown>>;
    batchCreate(entityType: string, records: Record<string, unknown>[], parentId?: number): Promise<ApiResponse<unknown>>;
    batchUpdate(entityType: string, updates: Array<{
        id: number;
        data: Record<string, unknown>;
    }>): Promise<ApiResponse<unknown>>;
    batchDelete(entityType: string, ids: number[]): Promise<ApiResponse<unknown>>;
}
