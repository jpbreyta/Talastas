# Digital Mapping of ICT Infrastructure and Gap Analysis System (Lipa City)

A specialized Geographic Information System (GIS) and Business Analytics platform designed to map, audit, and analyze Information and Communications Technology (ICT) assets across the 72 barangays and public institutions of Lipa City.

## Project Overview

Lipa City is a designated "Next Wave City," but its digital transformation is often hindered by a significant digital divide and fragmented data. This system serves as a Decision Support System (DSS), transforming raw inventory data into actionable insights to help the Local Government Unit (LGU) and DepEd identify connectivity gaps and allocate resources efficiently.

## Core Objectives

- **Geospatial Visualization**: Interactive mapping of ICT distribution using LeafletJS.
- **Automated Gap Analysis**: Identification of Infrastructure Deficits, Connectivity Divides, and Technological Obsolescence.
- **Decision Support**: A weighted deficiency-scoring algorithm to prioritize urgent barangay needs.
- **Predictive Analytics**: AI-driven insights to measure and monitor the city's digital progress.

## Tech Stack

The system is built using a modern Three-Tier Architecture for scalability and security:

- **Frontend**: LeafletJS (Spatial Mapping), Tailwind CSS (UI), ApexCharts (Analytics).
- **Backend**: Laravel 11 (PHP 8.2+) providing RESTful APIs and background job processing.
- **Database**: Supabase (PostgreSQL 15) with PostGIS for advanced spatial queries and real-time data sync.
- **AI Integration**: Custom algorithms for predictive modeling and automated diagnostic reporting.

## Key Features

▶ **Interactive Heatmaps**: Visualizes "hot spots" of digital inequity.  
▶ **Institutional Audit Dashboard**: Secure entry for ICT coordinators to manage hardware and network logs.  
▶ **Weighted Deficiency Scoring**: Computes gap severity using the formula:

  $$
  WDS = (w_1 \times \frac{total\_hardware}{total\_users}) + (w_2 \times avg\_hardware\_age) + (w_3 \times bandwidth\_speed)
  $$

▶ **Role-Based Access Control (RBAC)**: Separate interfaces for System Admins, City Planners, and Barangay Focal Persons.  
▶ **Evidence-Based Reporting**: Generates policy-ready PDF/Excel reports for budget hearings.

## System Architecture

The system processes data through a structured pipeline:

- **Input**: Field audit data and GPS geo-tagging.
- **Process**: Data scrubbing, PostGIS spatial analysis, and AI interpretation.
- **Output**: Real-time dashboards and predictive infrastructure scenarios.

## Standards & Privacy

- **Quality Standard**: Evaluated against ISO/IEC 25010:2023 for functional suitability and reliability.
- **Compliance**: Fully adheres to RA 10173 (Data Privacy Act of 2012) through data masking and anonymization.

## API Documentation

All API endpoints are prefixed with `/api/v1`. Base URL: `https://your-domain.com/api/v1`

### Authentication (Recommended: Custom API Key)

For Flutter app integration, use the **x-api-key** header. No login required.

**Generate an API Key (once):**
```
POST /api/v1/api-keys/generate
Body: {"name": "Flutter Mobile App"}
Returns: {"key": "sk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"}
```

**Use in Flutter:**
```dart
final headers = {
  'x-api-key': 'sk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  'Content-Type': 'application/json',
  'Accept': 'application/json',
};
```

### Endpoint Reference

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api-keys/generate` | No | Generate a new API key |
| GET | `/institutions` | No | Get all institutions (read-only) |
| GET | `/institutions/{id}` | No | Get single institution |
| POST | `/institutions` | Yes (x-api-key) | Create institution |
| PUT | `/institutions/{id}` | Yes (x-api-key) | Update institution |
| DELETE | `/institutions/{id}` | Yes (x-api-key) | Delete institution |
| GET | `/ict-inventory` | Yes (x-api-key) | Get all inventory items |
| GET | `/ict-inventory/{id}` | Yes (x-api-key) | Get single inventory item |
| POST | `/ict-inventory` | Yes (x-api-key) | Create inventory item |
| PUT | `/ict-inventory/{id}` | Yes (x-api-key) | Update inventory item |
| DELETE | `/ict-inventory/{id}` | Yes (x-api-key) | Delete inventory item |
| GET | `/analytics` | Yes (x-api-key) | Get all analytics data |
| POST | `/analytics/analyze/{id}` | Yes (x-api-key) | Analyze single institution |
| POST | `/analytics/analyze-all` | Yes (x-api-key) | Analyze all institutions |
| GET | `/map/institutions` | No | Institutions as GeoJSON |
| GET | `/barangays/geojson` | No | Barangays as GeoJSON |
| GET | `/map/barangays` | No | Barangays boundaries |
| GET | `/barangays` | No | Get all barangays |
| POST | `/institutions/geocode` | Yes (x-api-key) | Geocode institution location |

### Alternative: Sanctum Token Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/login` | No | Login with email/password, returns token |
| GET | `/auth/me` | Yes | Get current authenticated user |

**Institution Fields:**
```dart
{
  "name": "String (required)",
  "type": "School|Barangay Hall|Local Government Office (required)",
  "barangay_id": "Integer (required)",
  "latitude": "Double (optional)",
  "longitude": "Double (optional)",
  "student_population": "Integer (required for Schools)",
  "staff_population": "Integer (required for non-Schools)",
  "isp_provider": "String (optional)",
  "internet_speed": "Double (optional, Mbps)",
  "has_computer_lab": "Boolean",
  "number_of_computers": "Integer",
  "has_projected_whiteboard": "Boolean",
  "has_smart_tv": "Boolean",
  "has_public_address_system": "Boolean",
  "needs_maintenance": "Boolean",
  "notes": "String",
  "is_pilot_site": "Boolean",
  "requires_audit": "Boolean",
  "is_active": "Boolean"
}
```

**Inventory Item Fields:**
```dart
{
  "institution_id": "Integer (required)",
  "category": "String (computer, smart_tv, projector, pa_system - required)",
  "condition": "String (required)",
  "acquisition_year": "Integer 1900-current+1 (required)",
  "status": "active|retired (required)"
}
```

**Analytics Response:**
```dart
{
  "analytics": [...],
  "average_gap_score": 45.5,
  "institution_count": 120,
  "critical_areas": 23
}
```

## Flutter Integration Example (API Key)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class LipaApi {
  final String baseUrl;
  final String apiKey;

  LipaApi(this.baseUrl, this.apiKey);

  Map<String, String> get headers => {
    'x-api-key': apiKey,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  Future<List> getInstitutions() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/v1/institutions'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> getMapData() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/v1/map/institutions'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> getAnalytics() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/v1/analytics'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  Future<void> createInstitution(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/v1/institutions'),
      headers: headers,
      body: jsonEncode(data),
    );
  }
}
```

## The Development Team

- Madrid, Jaika Remina A.
- Perez, Aldren D.
- Reyta, John Paul Bryan D.