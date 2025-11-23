-- Query to get PC units with room_id, terminal_number, building_id, status, and notes
SELECT 
    a.id,
    a.asset_tag,
    a.asset_name,
    a.brand,
    a.model,
    a.serial_number,
    a.room_id,
    r.name AS room_name,
    r.building_id,
    b.name AS building_name,
    a.terminal_number,
    a.status,
    a.condition,
    a.notes,
    a.specifications,
    a.purchase_date,
    a.created_at,
    a.updated_at
FROM 
    assets a
LEFT JOIN 
    rooms r ON a.room_id = r.id
LEFT JOIN 
    buildings b ON r.building_id = b.id
WHERE 
    a.asset_type = 'Hardware'
    AND (a.category = 'Desktop' OR a.asset_name LIKE '%Computer%' OR a.asset_name LIKE '%PC%')
    AND a.room_id IS NOT NULL
ORDER BY 
    b.name, r.name, a.terminal_number;

-- Query with specific filters (example: only Active/In Use status)
SELECT 
    a.id,
    a.asset_tag,
    a.asset_name,
    a.room_id,
    r.name AS room_name,
    r.building_id,
    b.name AS building_name,
    a.terminal_number,
    a.status,
    a.notes
FROM 
    assets a
LEFT JOIN 
    rooms r ON a.room_id = r.id
LEFT JOIN 
    buildings b ON r.building_id = b.id
WHERE 
    a.asset_type = 'Hardware'
    AND (a.category = 'Desktop' OR a.asset_name LIKE '%Computer%')
    AND a.room_id IS NOT NULL
    AND a.status IN ('Active', 'In Use', 'Available')
ORDER BY 
    b.name, r.name, a.terminal_number;

-- Query grouped by building and room
SELECT 
    b.id AS building_id,
    b.name AS building_name,
    r.id AS room_id,
    r.name AS room_name,
    COUNT(a.id) AS total_pc_units,
    SUM(CASE WHEN a.status = 'Active' THEN 1 ELSE 0 END) AS active_units,
    SUM(CASE WHEN a.status = 'In Use' THEN 1 ELSE 0 END) AS in_use_units,
    SUM(CASE WHEN a.status = 'Available' THEN 1 ELSE 0 END) AS available_units,
    SUM(CASE WHEN a.status = 'Under Maintenance' THEN 1 ELSE 0 END) AS under_maintenance
FROM 
    buildings b
LEFT JOIN 
    rooms r ON b.id = r.building_id
LEFT JOIN 
    assets a ON r.id = a.room_id 
    AND a.asset_type = 'Hardware' 
    AND (a.category = 'Desktop' OR a.asset_name LIKE '%Computer%')
GROUP BY 
    b.id, b.name, r.id, r.name
ORDER BY 
    b.name, r.name;
