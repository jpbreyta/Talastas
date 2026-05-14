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

## The Development Team

- Madrid, Jaika Remina A.
- Perez, Aldren D.
- Reyta, John Paul Bryan D.