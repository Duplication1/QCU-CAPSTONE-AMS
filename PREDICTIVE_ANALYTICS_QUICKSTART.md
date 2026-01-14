# Predictive Analytics - Quick Start Guide

## 🎯 What is Predictive Analytics?

Predictive analytics uses **linear regression** and statistical models to forecast:
- When assets might fail
- Future maintenance needs
- Asset condition trends
- End-of-life predictions

## 🚀 Getting Started

### 1. Access the Dashboard
- Login as **Administrator**
- Navigate to **Analytics** → **Predictive Analytics**
- Wait for data to load (automatically fetches from API)

### 2. Understanding the Dashboard

#### Top Summary Cards
- **High Risk Assets**: Number of assets with critical/high failure risk
- **Avg Condition Score**: Current overall asset health (0-100)
- **Predicted Issues**: Expected problems next month
- **Model Accuracy**: How well the regression fits (R² score)

#### Main Charts

**1. Condition Degradation Trend**
- Shows how asset condition changes over time
- Blue line = historical data
- Orange dashed line = predictions (next 6 months)
- **Slope**: Rate of change (negative = getting worse)
- **R² Score**: Model reliability (higher = better)

**2. Maintenance Forecast**
- Purple bars = past issues
- Yellow bars = predicted future issues
- Helps plan technician schedules and budgets

**3. Risk Distribution**
- Pie chart showing how many assets are in each risk category
- Red = Critical, Orange = High, Yellow = Medium, Green = Low

**4. Lifecycle Analysis**
- Shows asset count and condition by age group
- Helps identify when assets typically degrade

**5. Critical Assets List**
- Real-time list of assets needing immediate attention
- Click to see details

## 📊 Reading the Statistics

### Linear Regression Metrics

**Slope**
- Positive (+): Condition improving
- Negative (-): Condition degrading
- Example: -1.5 means losing 1.5 condition points per month

**R² Score (Model Accuracy)**
- 0.0 = No correlation (poor model)
- 1.0 = Perfect correlation (excellent model)
- Generally:
  - **>0.8**: Excellent - trust the predictions
  - **0.6-0.8**: Good - predictions are reliable
  - **0.4-0.6**: Fair - use with caution
  - **<0.4**: Poor - need more data

**Trend**
- ↓ Degrading = Assets getting worse (red badge)
- ↑ Improving = Assets getting better (green badge)
- ↑ Increasing = More issues expected (orange badge)
- ↓ Decreasing = Fewer issues expected (green badge)

## 🎯 Taking Action

### If You See High Risk Assets
1. Check the **Critical Assets** list
2. Review each asset's condition and issue history
3. Schedule immediate inspection
4. Consider:
   - Preventive maintenance
   - Replacement if near end-of-life
   - Temporary replacement during repairs

### If Condition is Degrading
1. Note the **slope** value
2. If steep (e.g., -2 or worse):
   - Investigate root causes
   - Check maintenance procedures
   - Review procurement quality
3. Implement corrective actions:
   - Increase maintenance frequency
   - Staff training
   - Better quality assets

### If Maintenance Forecast is Increasing
1. Check predicted issue count
2. Plan ahead:
   - Schedule more technician time
   - Order spare parts
   - Allocate budget
   - Consider hiring temporary help

## 📈 Example Interpretations

### Scenario 1: Degrading with High R²
```
Slope: -1.8
R²: 0.92
Trend: ↓ Degrading
```
**Interpretation**: Assets are consistently declining at 1.8 points/month. The model is very reliable (R²=0.92). **Action needed!**

### Scenario 2: Improving with Low R²
```
Slope: +0.5
R²: 0.35
Trend: ↑ Improving
```
**Interpretation**: Data suggests slight improvement, but the model isn't reliable (R²=0.35). Could be random variation. **Need more data.**

### Scenario 3: Stable Maintenance
```
Predicted Issues: 15
Historical Average: 14
Trend: ↑ Increasing (slight)
```
**Interpretation**: Expect about the same workload next month. Plan accordingly.

## 💡 Tips for Better Predictions

### Improve Data Quality
1. **Log all issues** with correct asset links
2. **Update conditions** regularly (monthly recommended)
3. **Track maintenance** in asset history
4. **Complete asset details** (age, purchase date, etc.)

### Build Historical Data
- Minimum: 2-3 months for basic predictions
- Good: 6-12 months for reliable trends
- Best: 12+ months for accurate forecasting

### Regular Reviews
- Check dashboard weekly
- Compare predictions vs. actuals
- Adjust maintenance plans based on trends

## ⚠️ Common Issues

**"No data available"**
- Ensure assets exist in system
- Check that issues are being logged
- Verify asset_history is recording changes

**Low accuracy (R² < 0.5)**
- Need more historical data
- Data might be too variable
- Check for outliers or data errors

**Unexpected predictions**
- Review recent data for anomalies
- Check if bulk changes were made
- Verify data integrity

## 🔧 Technical Details

### How It Works
1. System queries 12 months of historical data
2. Calculates linear regression: `y = mx + b`
3. Uses slope (m) and intercept (b) to predict future
4. R² measures how well the line fits the data
5. Predictions extend 6 months into future

### Data Sources
- **assets** table: Current state and age
- **asset_history**: Condition changes over time
- **issues**: Reported problems and tickets

### Refresh Rate
- Data fetches on page load
- Refresh browser to update
- API generates real-time calculations

## 📞 Need Help?

1. Read the full documentation: `PREDICTIVE_ANALYTICS_README.md`
2. Check your browser console for errors (F12)
3. Review PHP error logs for backend issues
4. Verify database connectivity

## 🎓 Learning Resources

- **Linear Regression**: Basic statistical method for trend prediction
- **R-squared**: Measures correlation strength
- **Predictive Maintenance**: Proactive asset management
- **Time Series Analysis**: Analyzing data over time

---

**Last Updated**: January 2026
**Version**: 1.0
