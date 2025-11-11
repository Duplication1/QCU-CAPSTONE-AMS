# PC Components Tracking System

## Overview
The system now supports detailed tracking of individual PC units and their internal components. Each PC (e.g., th-1, th-2) can have multiple components tracked separately including CPU, RAM, motherboard, storage, and peripherals.

## Database Structure

### PC Units Table (`pc_units`)
Tracks individual PC workstations in each room:
- **id**: Primary key
- **room_id**: Foreign key to rooms table
- **terminal_number**: Terminal identifier (e.g., th-1, th-2)
- **pc_name**: Custom name for the PC (optional)
- **asset_tag**: Reference to main asset in assets table
- **status**: Active, Inactive, Under Maintenance, Disposed
- **notes**: Additional notes about the PC

### PC Components Table (`pc_components`)
Tracks individual components within each PC:
- **id**: Primary key
- **pc_unit_id**: Foreign key to pc_units table
- **component_type**: CPU, RAM, Motherboard, Storage, GPU, PSU, Case, Monitor, Keyboard, Mouse, Other
- **component_name**: Name/model of the component
- **brand**: Component manufacturer
- **model**: Model number
- **serial_number**: Unique serial number
- **specifications**: Detailed technical specs
- **purchase_date**: When it was purchased
- **purchase_cost**: Cost in PHP
- **warranty_expiry**: Warranty expiration date
- **status**: Working, Faulty, Replaced, Disposed
- **condition**: Excellent, Good, Fair, Poor
- **notes**: Additional notes

## Features

### For Administrators
1. **View All PCs**: See all PC units across all rooms
2. **Filter by Room**: Filter PCs by specific rooms
3. **Click PC to View Components**: Click any PC to see its detailed component list
4. **Component Details**: View complete information about each component including:
   - Component type and name
   - Brand and model
   - Specifications
   - Serial number
   - Purchase information
   - Current status and condition
   - Warranty information

### Component Categories
- **CPU**: Processor
- **RAM**: Memory modules
- **Motherboard**: Main board
- **Storage**: HDD/SSD drives
- **GPU**: Graphics card
- **PSU**: Power supply unit
- **Case**: PC case/chassis
- **Monitor**: Display
- **Keyboard**: Input device
- **Mouse**: Input device
- **Other**: Miscellaneous components

## Usage

### Accessing the System
1. Login as Administrator
2. Navigate to: `/view/Administrator/pc_components.php`
3. View list of all PC units

### Viewing PC Components
1. Click on any PC card
2. A modal will open showing:
   - PC information (terminal, room, status, asset tag)
   - Complete list of components with details
   - Component status and condition

### Sample Data Included
The system includes sample data for 3 PC units:
- **IK501-PC01 (th-1)**: Complete desktop with 9 components
- **IK501-PC02 (th-2)**: Partial setup with 3 components
- **IK502-PC01 (th-1)**: Another sample PC

## API Endpoints

### Get PC Details
**Endpoint**: `/controller/get_pc_details.php`

**Parameters**:
- `id`: Get specific PC by ID with all components
- `room_id`: Get all PCs in a room
- `room_id` + `terminal`: Get specific PC by room and terminal number

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "room_id": 1,
    "terminal_number": "th-1",
    "pc_name": "IK501-PC01",
    "status": "Active",
    "components": [
      {
        "id": 1,
        "component_type": "CPU",
        "component_name": "Intel Core i7-11700",
        "brand": "Intel",
        "specifications": "8 Cores, 16 Threads",
        "status": "Working",
        "condition": "Good"
      }
    ]
  }
}
```

## Database Setup

Run the updated `ams_database.sql` file which includes:
1. `pc_units` table creation
2. `pc_components` table creation
3. Sample data for 3 PCs with components
4. Foreign key relationships
5. Proper indexes for performance

## Models

### PCUnit.php
- `getAll()`: Get all PC units
- `getByRoom($room_id)`: Get PCs in specific room
- `getByIdWithComponents($id)`: Get PC with all components
- `getByRoomAndTerminal($room_id, $terminal)`: Find specific PC
- `create($data)`: Add new PC unit
- `update($id, $data)`: Update PC unit
- `delete($id)`: Remove PC unit

### PCComponent.php
- `getByPCUnit($pc_unit_id)`: Get all components for a PC
- `getById($id)`: Get specific component
- `create($data)`: Add new component
- `update($id, $data)`: Update component
- `delete($id)`: Remove component
- `getByType($type)`: Get all components of specific type
- `getFaultyComponents()`: Get all faulty components

## Future Enhancements
- [ ] Add/Edit/Delete PC units through UI
- [ ] Add/Edit/Delete components through UI
- [ ] Component replacement history
- [ ] Bulk import of PC configurations
- [ ] Component warranty alerts
- [ ] Maintenance scheduling per component
- [ ] Component upgrade recommendations
- [ ] Export component inventory reports
- [ ] QR code generation for components
- [ ] Component performance tracking

## Benefits
✅ Detailed inventory of every PC component  
✅ Easy identification of faulty hardware  
✅ Track component warranties and purchase info  
✅ Plan for upgrades and replacements  
✅ Quick troubleshooting - know exactly what's inside each PC  
✅ Better maintenance planning  
✅ Complete audit trail of PC configurations  
✅ Integration with existing asset management system
