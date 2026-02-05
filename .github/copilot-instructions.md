# GitHub Copilot Instructions for DemoticEdu Codebase

## Overview
This document provides essential guidance for AI coding agents working within the DemoticEdu codebase. Understanding the architecture, workflows, and conventions is crucial for effective contributions.

## Architecture
- **Frontend and Backend Separation**: The project is structured with a clear separation between frontend (HTML, CSS, JS) and backend (PHP) components. The frontend handles user interactions and displays data, while the backend manages data processing and API responses.
- **Data Flow**: Data is fetched from the backend using AJAX calls in `main.js`, which interacts with PHP scripts in the `Backend` directory. For example, `loadPrograms()` and `loadTestimonials()` functions fetch data from respective API endpoints.
- **Component Structure**: Key components include:
  - **HTML Files**: Structure the UI (e.g., `index.html`, `about.html`, `contact.html`).
  - **JavaScript**: Handles dynamic content loading and user interactions (e.g., `main.js`).
  - **PHP Scripts**: Process requests and serve data (e.g., `news.php`, `programs.php`).

## Developer Workflows
- **Building and Testing**: Ensure that the local server (XAMPP) is running. Use the browser to test changes in real-time. No specific build commands are required as the project is primarily HTML/CSS/JS with PHP.
- **Debugging**: Use browser developer tools to inspect elements and debug JavaScript. PHP errors can be logged in the `error_log` files located in the `Backend` directory.

## Project-Specific Conventions
- **File Naming**: Use lowercase and hyphens for file names (e.g., `testimonials.php`, `main.js`).
- **JavaScript Functions**: Follow a consistent naming convention for functions, typically using camelCase (e.g., `loadTestimonials`, `renderPrograms`).
- **CSS Classes**: Use BEM (Block Element Modifier) methodology for naming CSS classes to maintain clarity and avoid conflicts.

## Integration Points
- **API Endpoints**: The frontend communicates with the backend through defined API endpoints (e.g., `/api/programs.php`). Ensure that any new features include corresponding API support.
- **External Dependencies**: The project uses TinyMCE for rich text editing in the backend. Ensure to include necessary scripts in the HTML files where required.

## Communication Patterns
- **Data Handling**: Use `fetch` for API calls in JavaScript. Handle errors gracefully and provide user feedback when data loading fails.
- **Dynamic Content Injection**: Use functions like `inject()` in `main.js` to load HTML fragments dynamically into the DOM, ensuring a seamless user experience.

## Examples
- **Loading Programs**: The `loadPrograms()` function demonstrates how to fetch and process program data from the backend.
- **Testimonials Section**: The `initTestimonialsSection()` function initializes the testimonials carousel, showcasing how to render dynamic content based on API responses.

## Conclusion
This document serves as a foundational guide for AI coding agents to navigate and contribute effectively to the DemoticEdu codebase. For further assistance, refer to the specific file documentation and inline comments within the code.