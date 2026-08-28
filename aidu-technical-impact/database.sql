CREATE DATABASE IF NOT EXISTS aidutech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aidutech;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS project_media;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS testimonials;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS sectors;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS social_links;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL DEFAULT 'AID-U-TECHNICAL IMPACT',
    tagline VARCHAR(255) NOT NULL DEFAULT 'PRECISE AND CONCISE',
    phone VARCHAR(80) NOT NULL DEFAULT '',
    email VARCHAR(180) NOT NULL DEFAULT '',
    whatsapp VARCHAR(80) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    website VARCHAR(255) NOT NULL DEFAULT '',
    why_image VARCHAR(500) NOT NULL DEFAULT 'assets/images/why-site-context.jpg',
    hero_title VARCHAR(255) NOT NULL DEFAULT 'Surveying, Draftsmanship and Technical Solutions',
    hero_text TEXT NOT NULL,
    about_text TEXT NOT NULL,
    footer_text TEXT NOT NULL,
    primary_cta VARCHAR(80) NOT NULL DEFAULT 'Request a Consultation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    short_text VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(80) NOT NULL DEFAULT 'fa-solid fa-ruler-combined',
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sectors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(80) NOT NULL DEFAULT 'fa-solid fa-building',
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(80) NOT NULL,
    url VARCHAR(500) NOT NULL,
    icon_class VARCHAR(100) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    year_label VARCHAR(40) NOT NULL DEFAULT '',
    short_description VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    client_name VARCHAR(180) NOT NULL DEFAULT '',
    status ENUM('Completed','Ongoing','Planning') NOT NULL DEFAULT 'Completed',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    cover_image VARCHAR(500) NOT NULL DEFAULT '',
    project_video VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE project_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    media_type ENUM('image','video') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_media_project (project_id),
    CONSTRAINT fk_project_media_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(120) NOT NULL,
    role_company VARCHAR(180) NOT NULL,
    quote TEXT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    image VARCHAR(255) NOT NULL DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(80) NOT NULL DEFAULT '',
    service VARCHAR(150) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    status ENUM('New','Read','Replied') NOT NULL DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_status (status)
) ENGINE=InnoDB;

INSERT INTO settings (
    id,
    company_name,
    tagline,
    phone,
    email,
    whatsapp,
    address,
    website,
    why_image,
    hero_title,
    hero_text,
    about_text,
    footer_text,
    primary_cta
) VALUES (
    1,
    'AID-U-TECHNICAL IMPACT',
    'PRECISE AND CONCISE',
    '',
    '',
    '',
    'Accra, Ghana',
    '',
    'assets/images/why-site-context.jpg',
    'Surveying, Draftsmanship and Technical Solutions',
    'Accurate field information, precise technical drawings and dependable project support for land, buildings, roads and infrastructure.',
    'AID-U-TECHNICAL IMPACT brings architectural, engineering, surveying and technical drafting together to help clients move from site information to clear and buildable decisions.',
    'ARCHITECTURAL | ENGINEERING | SURVEYING',
    'Request a Consultation'
);

INSERT INTO services (title, short_text, description, icon, sort_order) VALUES
('Land and Boundary Surveying','Reliable measurements for land ownership and development.','Boundary definition, site measurements, coordinate work and survey documentation prepared with clear technical records.','fa-solid fa-ruler-combined',1),
('Topographical Surveying','Detailed site information for planning and design.','Capture existing levels, structures, roads, terrain and site features so designers and builders can make informed decisions.','fa-solid fa-map-location-dot',2),
('CAD and Technical Drafting','Precise drawings that communicate the design.','Professional plans, elevations, sections, details and technical drafting support for architectural and engineering work.','fa-solid fa-compass-drafting',3),
('Setting Out and Site Support','Transfer approved dimensions and positions to site.','Practical setting-out support, control points, alignment checks and technical assistance during construction.','fa-solid fa-location-crosshairs',4),
('As-Built Documentation','Record what was actually constructed.','As-built measurements and drawings that document completed work for handover, records and future maintenance.','fa-solid fa-drafting-compass',5),
('Mapping and Site Analysis','Turn field information into useful site insight.','Maps, site plans and organized spatial information to support property, development and infrastructure decisions.','fa-solid fa-layer-group',6);

INSERT INTO sectors (title, description, icon, sort_order) VALUES
('Residential Development','Homes, estates, extensions and private property development.','fa-solid fa-house',1),
('Commercial Buildings','Offices, shops, mixed-use and business developments.','fa-solid fa-building',2),
('Roads and Transport','Road corridors, access routes, drainage and transport infrastructure.','fa-solid fa-road',3),
('Land and Property','Boundary, parcel, subdivision and land-development work.','fa-solid fa-map',4),
('Construction','Site set-out, as-built documentation and technical support.','fa-solid fa-helmet-safety',5),
('Infrastructure','Surveying and drafting support for public and private infrastructure.','fa-solid fa-bridge',6),
('Real Estate','Site information and technical documentation for property decisions.','fa-solid fa-city',7),
('Planning and Design','Accurate existing-condition information for designers and planners.','fa-solid fa-draw-polygon',8);

INSERT INTO projects (
    title, category, location, year_label, short_description, description,
    client_name, status, featured, cover_image, project_video
) VALUES
(
    'Residential Site and Access Study',
    'Site Survey / Drafting',
    'Accra, Ghana',
    '2026',
    'Site context documentation for a residential development.',
    'This project demonstrates a complete site-to-drawing workflow for a residential development. Field observations are organized around existing houses, the access road, site boundaries, levels and surrounding development. The project record provides a clear place for survey photographs, measurements, CAD drawings, setting-out information and future progress videos.',
    'Private Client',
    'Completed',
    1,
    'assets/images/project-accra-houses.jpg',
    ''
),
(
    'Urban Road and Building Context',
    'Surveying / Mapping',
    'Greater Accra',
    '2026',
    'Real-world urban context used to communicate site conditions.',
    'This project focuses on the relationship between an urban access road, existing buildings and the surrounding site. It shows how surveying and technical drafting can support road improvements, building development, access planning and construction coordination. Photographs, field observations, drawings and progress videos can be added through the admin dashboard as the real project develops.',
    'Development Client',
    'Ongoing',
    1,
    'assets/images/project-building-construction.jpg',
    ''
),
(
    'Property Development Documentation',
    'Draftsmanship / Site Support',
    'Accra, Ghana',
    '2026',
    'Technical documentation for a developing property site.',
    'This project provides a complete documentation space for a developing property. The administrator can replace the starter photographs with real site images, upload multiple progress videos, record survey findings and describe the work completed from initial site assessment through drafting and construction support. The goal is to give visitors a clear picture of the project purpose, site context, process and outcome.',
    'Private Client',
    'Planning',
    0,
    'assets/images/project-jamestown-house.jpg',
    ''
);

INSERT INTO project_media (project_id, media_type, file_path, caption, sort_order) VALUES
(1,'image','assets/images/project-accra-houses.jpg','Residential site and surrounding building context',1),
(1,'image','assets/images/splash-real-site-road.jpg','Real site and road context',2),
(2,'image','assets/images/project-building-construction.jpg','Building construction context',1),
(2,'image','assets/images/project-accra-houses.jpg','Existing urban building context',2),
(3,'image','assets/images/project-jamestown-house.jpg','Existing property context',1),
(3,'image','assets/images/splash-real-site-road.jpg','Road and site environment',2);

INSERT INTO testimonials (client_name, role_company, quote, rating, active) VALUES
('Private Client','Residential Development','The site information and drawings gave us a much clearer understanding of the property before moving forward.',5,1),
('Development Client','Property Project','The work was organized, practical and easy for the project team to understand.',5,1),
('Project Partner','Construction Support','The technical documentation made coordination between the site and drawing team much easier.',5,1);

-- Social media is intentionally empty. Add real accounts through Admin > Social Media.

-- No administrator is inserted here. Register the first administrator through Admin > Register.


INSERT INTO contact_messages (name,email,phone,service,message,status) VALUES
('Kwame Mensah','kwame@example.com','0240000000','Land and Boundary Surveying','I would like to discuss a boundary survey for a residential property.','New'),
('Ama Owusu','ama@example.com','0550000000','CAD and Technical Drafting','Please provide information about drafting support for a proposed building.','New'),
('Daniel Boateng','daniel@example.com','0200000000','Topographical Surveying','I need a topographical survey for a development site.','New');
