/**
 * TCS ERP MCP Server Types
 */

// API Response types
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  timestamp: string;
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  pagination: {
    total: number;
    count: number;
    per_page: number;
    current_page: number;
    total_pages: number;
    links: {
      next: string | null;
      previous: string | null;
    };
  };
}

// Entity types
export interface Project {
  id: number;
  name: string;
  project_number: string;
  partner_id: number | null;
  stage_id: number | null;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface Room {
  id: number;
  project_id: number;
  name: string;
  room_code: string | null;
  sort_order: number;
}

export interface RoomLocation {
  id: number;
  room_id: number;
  name: string;
  sort_order: number;
}

export interface CabinetRun {
  id: number;
  room_location_id: number;
  name: string;
  sort_order: number;
}

export interface Cabinet {
  id: number;
  cabinet_run_id: number;
  project_id: number | null;
  cabinet_number: string | null;
  full_code: string | null;
  length_inches: number | null;
  height_inches: number | null;
  depth_inches: number | null;
  linear_feet: number | null;
  drawer_count: number;
  door_count: number;
  construction_type: string;
  shop_notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface CabinetSection {
  id: number;
  cabinet_id: number;
  section_type: string;
  position: number;
  width_inches: number | null;
  height_inches: number | null;
}

export interface Drawer {
  id: number;
  cabinet_section_id: number;
  position: number;
  height_inches: number | null;
  width_inches: number | null;
  depth_inches: number | null;
}

export interface Door {
  id: number;
  cabinet_section_id: number;
  position: number;
  width_inches: number | null;
  height_inches: number | null;
  door_style: string | null;
}

export interface Product {
  id: number;
  name: string;
  sku: string | null;
  description: string | null;
  category_id: number | null;
  price: number | null;
}

export interface Partner {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  type: string;
}

export interface Employee {
  id: number;
  name: string;
  email: string | null;
  department_id: number | null;
}

export interface Task {
  id: number;
  name: string;
  description: string | null;
  status: string;
  project_id: number | null;
  assigned_to: number | null;
  due_date: string | null;
}

export interface ConstructionTemplate {
  id: number;
  name: string;
  is_default: boolean;
  face_frame_stile_width_inches: number | null;
  face_frame_rail_width_inches: number | null;
  material_thickness_inches: number | null;
}

export interface RhinoExtractionJob {
  id: number;
  uuid: string;
  project_id: number | null;
  status: string;
  cabinets_extracted: number;
  cabinets_imported: number;
  cabinets_pending_review: number;
  created_at: string;
  completed_at: string | null;
}

export interface RhinoExtractionReview {
  id: number;
  uuid: string;
  extraction_job_id: number;
  cabinet_number: string | null;
  confidence_score: number;
  status: string;
  review_type: string;
  extraction_data: Record<string, unknown>;
}

export interface WebhookSubscription {
  id: number;
  name: string | null;
  url: string;
  events: string[];
  is_active: boolean;
}

// Filter types
export interface ProjectFilters {
  status?: string;
  partner_id?: number;
  stage_id?: number;
  search?: string;
}

export interface CabinetFilters {
  project_id?: number;
  cabinet_run_id?: number;
  construction_type?: string;
  search?: string;
}

export interface TaskFilters {
  status?: string;
  project_id?: number;
  assigned_to?: number;
}

export interface ProductFilters {
  category_id?: number;
  search?: string;
}

// Tool input types
export interface CreateCabinetInput {
  cabinet_run_id: number;
  project_id?: number;
  cabinet_number?: string;
  length_inches?: number;
  height_inches?: number;
  depth_inches?: number;
  drawer_count?: number;
  door_count?: number;
  construction_type?: string;
}

export interface UpdateCabinetInput {
  id: number;
  cabinet_number?: string;
  length_inches?: number;
  height_inches?: number;
  depth_inches?: number;
  drawer_count?: number;
  door_count?: number;
  shop_notes?: string;
}

export interface TriggerExtractionInput {
  project_id?: number;
  force?: boolean;
  include_fixtures?: boolean;
  auto_approve_high_confidence?: boolean;
}

export interface ApproveReviewInput {
  review_id: number;
  corrections?: {
    width?: number;
    height?: number;
    depth?: number;
  };
  notes?: string;
  create_cabinet?: boolean;
}
