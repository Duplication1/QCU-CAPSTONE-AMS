# Laboratory Staff Analytics Dashboard

## Overview
A comprehensive analytics dashboard for Laboratory Staff to monitor asset usage, maintenance reports, and equipment condition statistics in real-time.

## Features

### 1. Key Metrics Cards
- **Total Assets**: Overview of all assets in the system
- **In Use**: Number of assets currently being used
- **Available**: Assets ready for deployment
- **Under Maintenance**: Assets currently being serviced
- **Utilization Rate**: Percentage of assets actively in use
- **Active Borrows**: Current borrowing requests

### 2. Equipment Condition Statistics
- Visual doughnut chart showing distribution of asset conditions:
  - Excellent
  - Good
  - Fair
  - Poor
  - Non-Functional
- Real-time percentage breakdown
- Color-coded for easy identification

### 3. Asset Distribution by Type
- Bar chart displaying asset categories
- Shows top asset types in the inventory
- Helps identify which equipment types are most prevalent

### 4. Maintenance Reports (Last 30 Days)
- Horizontal bar chart of maintenance issues by category
- Tracks recent maintenance activity
- Identifies problem areas requiring attention

### 5. Asset Usage Trend (6 Months)
- Line chart showing borrowing activity over time
- Identifies usage patterns and trends
- Helps with capacity planning

### 6. Room Utilization Analysis
- Grouped bar chart comparing:
  - Total assets per room
  - Assets in use per room
  - Available assets per room
- Shows top 8 rooms by asset count
- Helps optimize space allocation

### 7. Maintenance Status Overview
- Three-panel summary showing:
  - Open Issues (requires attention)
  - In Progress (being worked on)
  - Resolved (completed)
- Color-coded status indicators

## File Structure

```
view/LaboratoryStaff/
├── analytics.php                    # Main analytics dashboard page

controller/
├── get_labstaff_analytics.php       # AJAX endpoint for analytics data

view/components/
├── sidebar.php                      # Updated with Analytics link
```

## API Endpoints

### GET /controller/get_labstaff_analytics.php

Query parameters:
- `action`: Type of data to retrieve

Available actions:
1. `overview` - Get summary metrics
2. `asset_types` - Get asset distribution by type
3. `usage_trend` - Get borrowing trend (default: 6 months)
4. `maintenance_by_category` - Get maintenance issues (default: 30 days)
5. `room_utilization` - Get room utilization data
6. `condition_trend` - Get condition changes over time

Example:
```javascript
fetch('../../controller/get_labstaff_analytics.php?action=overview')
  .then(response => response.json())
  .then(data => console.log(data));
```

## Technologies Used

- **Backend**: PHP 7.4+, MySQL
- **Frontend**: HTML5, Tailwind CSS
- **Charts**: Chart.js 4.4.0
- **Icons**: Font Awesome 6.x

## Database Tables Used

- `assets` - Asset inventory data
- `asset_borrowing` - Borrowing records
- `issues` - Maintenance and issue tracking
- `rooms` - Room information
- `asset_history` - Asset change history

## Access Control

- **Role Required**: Laboratory Staff
- **Session Check**: Validates user authentication and role
- **Redirect**: Unauthorized users redirected to login page

## Navigation

Access the analytics dashboard:
1. Log in as Laboratory Staff
2. Click "Analytics" in the sidebar (second item after Dashboard)
3. View comprehensive analytics and charts

## Chart Interactions

All charts are interactive:
- **Hover**: View detailed tooltips with exact values and percentages
- **Responsive**: Automatically adjusts to screen size
- **Color-coded**: Consistent color scheme for easy interpretation

## Performance Considerations

- Efficient SQL queries with proper indexing
- Data aggregation at database level
- Minimal client-side processing
- Responsive design for all screen sizes

## Future Enhancements

Potential additions:
- Export analytics to PDF/Excel
- Custom date range selection
- Predictive analytics for maintenance scheduling
- Asset lifecycle forecasting
- Comparative analytics (month-over-month, year-over-year)
- Real-time updates via WebSocket
- Drill-down capabilities for detailed views

## Troubleshooting

### Charts not displaying
- Verify Chart.js CDN is accessible
- Check browser console for JavaScript errors
- Ensure database connection is established

### No data showing
- Verify assets exist in the database
- Check date ranges for trend data
- Confirm user has Laboratory Staff role

### Permission errors
- Verify session is active
- Check user role in database
- Clear browser cache and cookies

## Support

For issues or questions:
1. Check database connectivity
2. Verify user permissions
3. Review browser console for errors
4. Check PHP error logs

## Version History

- **v1.0.0** (2024) - Initial release
  - Equipment condition statistics
  - Asset usage trends
  - Maintenance reports
  - Room utilization analysis
