# Predictive Analytics - Linear Regression Implementation

## Overview
This implementation provides advanced predictive analytics for asset management using **linear regression** statistical models. The system analyzes historical data to forecast asset failures, maintenance needs, and lifecycle trends.

## Features

### 1. **Asset Failure Risk Prediction**
Uses multiple factors to calculate risk scores (0-100):
- **Condition Score**: Current asset condition (Excellent=100, Good=75, Fair=50, Poor=25, Non-Functional=0)
- **Age Factor**: Asset age normalized over 3-year lifecycle
- **Issue Factor**: Number of reported issues/tickets
- **Degradation Factor**: Recent condition changes (last 90 days)

**Formula**:
```
Risk Score = (100 - Condition Score) + Age Factor + Issue Factor + Degradation Factor
```

**Risk Levels**:
- **Critical**: Score ≥ 70
- **High**: Score ≥ 50
- **Medium**: Score ≥ 30
- **Low**: Score < 30

### 2. **Condition Degradation Trend (Linear Regression)**
Analyzes condition changes over 12 months and predicts next 6 months.

**Linear Regression Model**:
```
y = mx + b
where:
  y = condition score
  x = time period (month index)
  m = slope (rate of change)
  b = intercept
```

**Calculation**:
- Collects monthly average condition scores
- Applies least squares regression
- Calculates R² (coefficient of determination) for model accuracy
- Predicts future 6 months using regression line

**Metrics Provided**:
- **Slope**: Rate of degradation/improvement per month
- **R² Score**: Model fit quality (0-1, higher is better)
- **Trend**: Degrading (slope < 0) or Improving (slope > 0)

### 3. **Maintenance Forecast (Issue Prediction)**
Predicts future maintenance needs based on historical issue trends.

**Process**:
1. Aggregate monthly issue counts (past 12 months)
2. Apply linear regression to identify trend
3. Forecast next 6 months of expected issues
4. Identify increasing/decreasing maintenance demands

### 4. **Asset Lifecycle Analysis**
Segments assets by age and tracks condition:
- **New (0-6 months)**
- **Young (6-12 months)**
- **Active (1-2 years)**
- **Mature (2-3 years)**
- **Aging (3+ years)**

Provides average condition score per lifecycle stage.

### 5. **Critical Assets Identification**
Real-time identification of assets requiring immediate attention:
- Poor or Non-Functional condition
- Under Maintenance status
- High issue count
- Sorted by urgency

## Mathematical Implementation

### Linear Regression Formula

```php
function calculateLinearRegression($x_values, $y_values) {
    $n = count($x_values);
    
    // Calculate sums
    $sum_x = Σx
    $sum_y = Σy
    $sum_xy = Σ(xy)
    $sum_x2 = Σ(x²)
    
    // Slope (m)
    m = (n·Σ(xy) - Σx·Σy) / (n·Σ(x²) - (Σx)²)
    
    // Intercept (b)
    b = (Σy - m·Σx) / n
    
    // R-squared
    mean_y = Σy / n
    SS_tot = Σ(y - mean_y)²
    SS_res = Σ(y - predicted_y)²
    R² = 1 - (SS_res / SS_tot)
    
    return [slope: m, intercept: b, r_squared: R²]
}
```

### Prediction Formula
```
predicted_value = slope × future_time_index + intercept
```

## API Endpoint

### `GET /controller/get_predictive_analytics.php`

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "asset_failure_risk": [
      {
        "id": 123,
        "asset_tag": "2022-07-01-12345",
        "asset_name": "Desktop Computer",
        "condition": "Fair",
        "age_days": 730,
        "issue_count": 3,
        "risk_score": 45.5,
        "risk_level": "Medium"
      }
    ],
    "condition_degradation": {
      "historical": [
        {"month": "2025-01", "score": 78.5},
        {"month": "2025-02", "score": 75.2}
      ],
      "regression": {
        "slope": -1.23,
        "intercept": 82.4,
        "r_squared": 0.87
      },
      "predictions": [73.1, 71.9, 70.7, 69.5, 68.3, 67.1],
      "trend": "degrading"
    },
    "maintenance_forecast": {
      "historical": [...],
      "regression": {...},
      "predictions": [12, 13, 14, 15, 16, 17],
      "trend": "increasing"
    },
    "lifecycle_predictions": [...],
    "critical_assets": [...]
  },
  "generated_at": "2026-01-14 12:00:00"
}
```

## Dashboard Features

### Interactive Visualizations

1. **Summary Cards**
   - High Risk Asset Count
   - Average Condition Score
   - Predicted Issues (next month)
   - Model Accuracy (R²)

2. **Condition Trend Chart**
   - Line chart showing historical + predicted values
   - Visual distinction between actual and forecasted data
   - Displays slope, R², and 6-month forecast

3. **Maintenance Forecast Chart**
   - Bar chart with historical and predicted issues
   - Trend indicator (increasing/decreasing)
   - Statistical metrics

4. **Risk Distribution**
   - Doughnut chart showing risk level distribution
   - Color-coded by severity
   - Detailed counts

5. **Lifecycle Analysis**
   - Dual-axis chart (count + condition)
   - Asset distribution by age group

6. **Critical Assets List**
   - Scrollable list of high-priority assets
   - Condition badges
   - Issue counts and age

## Usage Instructions

### 1. Access the Dashboard
Navigate to: `/view/Administrator/predictive_analytics.php`

### 2. Interpreting the Data

**Slope Interpretation**:
- **Negative slope**: Condition degrading over time (needs attention)
- **Positive slope**: Condition improving
- **Value magnitude**: Rate of change per month

**R² Score Interpretation**:
- **0.8 - 1.0**: Excellent fit, predictions highly reliable
- **0.6 - 0.8**: Good fit, predictions reasonably reliable
- **0.4 - 0.6**: Moderate fit, use with caution
- **< 0.4**: Poor fit, more data needed

### 3. Taking Action

**For High Risk Assets**:
1. Check critical assets list
2. Review issue history
3. Schedule preventive maintenance
4. Consider replacement if end-of-life

**For Degrading Trends**:
1. Investigate root causes
2. Increase maintenance frequency
3. Review asset quality/procurement

**For Maintenance Forecasts**:
1. Plan resource allocation
2. Schedule technician availability
3. Budget for predicted issues

## Data Requirements

**Minimum Requirements**:
- At least 2 months of historical data for regression
- Asset condition tracking
- Issue/ticket logging
- Asset age information

**Optimal Requirements**:
- 12+ months of historical data
- Regular condition updates
- Detailed issue tracking with asset links
- Complete asset metadata

## Technical Details

### Database Tables Used
- `assets`: Asset inventory and current state
- `asset_history`: Condition changes and lifecycle events
- `issues`: Reported problems and tickets

### Performance Considerations
- Queries optimized with indexes on `created_at`, `asset_id`
- Aggregations performed at database level
- Results cached where appropriate
- Pagination for large datasets

### Accuracy Improvements
1. **More data**: Longer history = better predictions
2. **Regular updates**: Consistent condition tracking
3. **Detailed logging**: Link all issues to specific assets
4. **Clean data**: Remove outliers and anomalies

## Future Enhancements

### Potential Additions
1. **Multiple regression**: Factor in multiple variables simultaneously
2. **Polynomial regression**: Capture non-linear trends
3. **Seasonal decomposition**: Account for periodic patterns
4. **Machine learning**: Neural networks for complex patterns
5. **Confidence intervals**: Prediction ranges with probability
6. **What-if scenarios**: Simulate different maintenance strategies

### Advanced Models
- **ARIMA**: Time series forecasting
- **Prophet**: Facebook's time series tool
- **Random Forest**: Classification of failure risk
- **Survival Analysis**: Time-to-failure predictions

## Troubleshooting

### "No data available"
- Ensure assets have been created
- Check that issues are logged
- Verify asset_history is tracking changes

### Low R² scores
- Need more historical data
- Data may have high variability
- Check for data quality issues

### Unexpected predictions
- Review outliers in historical data
- Verify data integrity
- Check calculation logic

## References

- **Linear Regression**: https://en.wikipedia.org/wiki/Linear_regression
- **R-squared**: https://en.wikipedia.org/wiki/Coefficient_of_determination
- **Predictive Maintenance**: https://en.wikipedia.org/wiki/Predictive_maintenance

## Support

For issues or questions:
1. Check this documentation
2. Review the code comments in `get_predictive_analytics.php`
3. Inspect browser console for JavaScript errors
4. Check PHP error logs for backend issues
