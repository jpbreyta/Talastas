# Institution Management UI Documentation

## Overview
The Institution Management system provides a complete CRUD interface for managing educational and public institutions within the Lipa City ICT Mapping application. The UI tier focuses on user-friendly data entry, visualization, and mapping integration using GeoJSON for spatial representation.

## UI Components

### 1. Institution Index Page (`/institutions`)
**Purpose**: Displays all institutions in a card-based grid layout
**Features**:
- **Card Design**: Each institution is displayed in an attractive card with:
  - Institution name and type
  - Barangay location
  - Student population (if available)
  - Pilot site indicator badge
- **Actions**: View, Edit, Delete buttons per institution
- **Pagination**: Laravel pagination for large datasets
- **Empty State**: Helpful messaging when no institutions exist

**Visual Elements**:
- Gradient header with "Add Institution" button
- SVG icons throughout (school, location, users, actions)
- Hover effects and transitions
- Responsive grid (1 column mobile, 2 tablet, 3 desktop)

### 2. Institution Show Page (`/institutions/{id}`)
**Purpose**: Detailed view of a single institution
**Features**:
- **Header Section**: Institution name, type, and action buttons
- **Pilot Site Banner**: Highlighted banner for pilot institutions
- **Information Cards**:
  - **Basic Information**: Name, type, sector, barangay, coordinates, population, internet speed
  - **ICT Facilities**: Computer lab, smart TV, whiteboard, PA system with yes/no badges
- **ICT Inventory Table**: Related equipment with condition/status badges
- **Navigation**: Links to edit and return to list

**Visual Elements**:
- Color-coded card headers (emerald, purple, orange gradients)
- SVG icons for each data field
- Status badges (green for yes, red for no, colored for conditions)
- Responsive two-column layout

### 3. Institution Edit Page (`/institutions/{id}/edit`)
**Purpose**: Comprehensive form for editing institution data
**Features**:
- **Sectioned Form**: Organized into logical sections:
  - **Basic Information**: Name, type, sector type, student population
  - **Location & Connectivity**: Barangay, coordinates, internet speed
  - **ICT Facilities**: Checkboxes for available equipment
  - **Status & Notes**: Pilot site, maintenance needs, additional notes
- **Form Validation**: Real-time validation with error messages
- **Action Buttons**: Update and Cancel with proper styling

**Visual Elements**:
- Color-coded section headers with gradients
- SVG icons for all form labels
- Enhanced input styling with focus states
- Checkbox groups with descriptive icons

### 4. Institution Create Page (`/institutions/create`)
**Purpose**: New institution creation form
**Features**: Same form structure as edit page but for new entries

## Map Integration

### GeoJSON Data Flow
1. **Static GeoJSON File**: `public/data/lipa-city-barangays.geojson`
   - Contains Lipa City barangay boundaries
   - Used for base map layer display
   - Provides spatial context for institutions

2. **Dynamic Institution GeoJSON**: API endpoint `/api/v1/map/institutions`
   - Returns institutions as GeoJSON FeatureCollection
   - Each feature contains:
     - Point geometry (longitude, latitude)
     - Institution properties (name, type, facilities, etc.)
     - Computed analytics (gap score, data completeness)

### Map Visualization
- **Circle Markers**: Institutions displayed as colored circles
  - Size based on gap score severity
  - Color indicates connectivity level (green=good, red=critical)
  - Border weight indicates data completeness
- **Heatmap Layer**: Optional heat density visualization
- **Popup Information**: Detailed institution data on click
- **Interactive Controls**: Toggle layers, clear drawings

## Data Structure

### Institution Model Fields
```php
- name: string (required)
- type: enum (School, Hospital, Government, Library, Community Center, Other)
- sector_type: string (optional)
- barangay_name: string (required)
- latitude: decimal (required)
- longitude: decimal (required)
- student_population: integer (optional)
- internet_speed: decimal (optional)
- has_computer_lab: boolean
- number_of_computers: integer (optional)
- has_smart_tv: boolean
- has_projected_whiteboard: boolean
- has_public_address_system: boolean
- needs_maintenance: boolean
- is_pilot_site: boolean
- notes: text (optional)
```

### GeoJSON Structure
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [longitude, latitude]
      },
      "properties": {
        "id": 1,
        "name": "Institution Name",
        "type": "School",
        "barangay": "Barangay Name",
        "internet_speed": 50,
        "has_lab": true,
        "gap_score": 75.5,
        "data_completeness": 85
      }
    }
  ]
}
```

## User Interactions

### CRUD Operations
1. **Create**: Form submission → validation → database storage → redirect to index
2. **Read**: Database query → view rendering with relationships (ICT inventory)
3. **Update**: Form submission → validation → database update → redirect to index
4. **Delete**: Confirmation dialog → database deletion → redirect to index

### Map Interactions
- **Institution Markers**: Click for detailed popup, hover for size increase
- **Barangay Boundaries**: Hover for connectivity info, click for basic info
- **Drawing Tools**: Create polygons/rectangles for area analysis
- **Layer Toggles**: Show/hide heatmap and barangay boundaries

## Form Validation

### Frontend Validation
- HTML5 required attributes
- Input type constraints (number, text)
- Real-time visual feedback

### Backend Validation
- Laravel validation rules in InstitutionController
- Required fields: name, type, barangay_name, latitude, longitude
- Data type validation: numeric for coordinates, boolean for checkboxes
- Range validation: latitude (-90 to 90), longitude (-180 to 180)

## Styling & UX

### Design System
- **Tailwind CSS**: Utility-first styling framework
- **Color Palette**: Blue/indigo gradients for headers, semantic colors for status
- **Typography**: Clear hierarchy with appropriate font weights
- **Icons**: Heroicons SVG set for consistency

### Responsive Design
- **Mobile First**: Optimized for mobile devices
- **Breakpoint System**: sm/md/lg/xl responsive classes
- **Touch Friendly**: Adequate button sizes and spacing

### Accessibility
- **Semantic HTML**: Proper form structure and labels
- **ARIA Attributes**: Screen reader support for popovers
- **Color Contrast**: WCAG compliant color combinations
- **Keyboard Navigation**: Focus management and tab order

## Performance Considerations

### UI Optimization
- **Lazy Loading**: Map data loaded asynchronously
- **Pagination**: Large datasets split into pages
- **Efficient Queries**: Eager loading of relationships
- **Minimal Re-renders**: Optimized form interactions

### Map Performance
- **Clustering**: Avoid marker overload
- **Layer Management**: Toggle visibility to reduce DOM elements
- **Asynchronous Loading**: GeoJSON fetched after page load

## Integration Points

### Related Systems
- **ICT Inventory**: Related equipment management
- **Analytics**: Gap score calculations and reporting
- **Barangay Management**: Spatial boundary data
- **User Management**: Audit trail and permissions

### API Endpoints
- `GET /institutions` - List all institutions
- `POST /institutions` - Create new institution
- `GET /institutions/{id}` - Show specific institution
- `PUT /institutions/{id}` - Update institution
- `DELETE /institutions/{id}` - Delete institution
- `GET /api/v1/map/institutions` - GeoJSON data for mapping

This documentation covers the complete UI tier implementation for institution management, focusing on user experience, data visualization, and seamless integration with the mapping system.