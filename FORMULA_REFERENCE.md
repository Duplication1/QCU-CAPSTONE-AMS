# Linear Regression Formula Reference Card

## 📐 Core Linear Regression Equations

### Basic Model
```
y = mx + b

where:
  y = predicted value (dependent variable)
  x = time period or independent variable
  m = slope (rate of change)
  b = y-intercept (starting value)
```

---

## 🧮 Calculating Regression Coefficients

### Given Data Points
```
x = [x₁, x₂, x₃, ..., xₙ]
y = [y₁, y₂, y₃, ..., yₙ]
n = number of data points
```

### Slope (m)
```
         n·Σ(xᵢyᵢ) - Σxᵢ·Σyᵢ
m = ─────────────────────────────
         n·Σ(xᵢ²) - (Σxᵢ)²


Example:
  x = [0, 1, 2, 3, 4]
  y = [100, 95, 87, 82, 75]
  n = 5
  
  Σx = 10
  Σy = 439
  Σ(xy) = 795
  Σ(x²) = 30
  
  m = (5×795 - 10×439) / (5×30 - 10²)
  m = (3975 - 4390) / (150 - 100)
  m = -415 / 50
  m = -8.3
  
  Interpretation: Losing 8.3 points per time period
```

### Intercept (b)
```
     Σyᵢ - m·Σxᵢ
b = ─────────────
          n


Example (using m = -8.3 from above):
  b = (439 - (-8.3)×10) / 5
  b = (439 + 83) / 5
  b = 522 / 5
  b = 104.4
  
  Interpretation: Starting value at time 0
```

### Final Equation
```
y = -8.3x + 104.4

Predictions:
  Month 5: y = -8.3(5) + 104.4 = 62.9
  Month 6: y = -8.3(6) + 104.4 = 54.6
```

---

## 📊 R-Squared (R²) - Coefficient of Determination

### Formula
```
       SS_res
R² = 1 - ───────
       SS_tot

where:
  SS_res = Σ(yᵢ - ŷᵢ)²     (Residual sum of squares)
  SS_tot = Σ(yᵢ - ȳ)²      (Total sum of squares)
  
  yᵢ  = actual value
  ŷᵢ  = predicted value (from regression line)
  ȳ   = mean of all y values
```

### Step-by-Step Calculation

```
1. Calculate mean of y:
   ȳ = Σyᵢ / n

2. Calculate predicted values using y = mx + b:
   ŷᵢ = m·xᵢ + b

3. Calculate SS_tot (total variance):
   SS_tot = Σ(yᵢ - ȳ)²

4. Calculate SS_res (residual variance):
   SS_res = Σ(yᵢ - ŷᵢ)²

5. Calculate R²:
   R² = 1 - (SS_res / SS_tot)
```

### Example
```
Data:
  x = [0, 1, 2, 3, 4]
  y = [100, 95, 87, 82, 75]
  
Regression: y = -8.3x + 104.4

Step 1: Mean
  ȳ = 439 / 5 = 87.8

Step 2: Predicted values
  ŷ = [104.4, 96.1, 87.8, 79.5, 71.2]

Step 3: SS_tot
  SS_tot = (100-87.8)² + (95-87.8)² + (87-87.8)² + (82-87.8)² + (75-87.8)²
  SS_tot = 148.84 + 51.84 + 0.64 + 33.64 + 163.84
  SS_tot = 398.8

Step 4: SS_res
  SS_res = (100-104.4)² + (95-96.1)² + (87-87.8)² + (82-79.5)² + (75-71.2)²
  SS_res = 19.36 + 1.21 + 0.64 + 6.25 + 14.44
  SS_res = 41.9

Step 5: R²
  R² = 1 - (41.9 / 398.8)
  R² = 1 - 0.105
  R² = 0.895
  
Interpretation: 89.5% of variance explained (excellent fit!)
```

---

## 🎯 Asset Risk Score Formula

### Complete Formula
```
Risk Score = Base_Risk + Age_Factor + Issue_Factor + Degradation_Factor

where:
  Base_Risk = 100 - Condition_Score
  
  Condition_Score mapping:
    Excellent      → 100
    Good           → 75
    Fair           → 50
    Poor           → 25
    Non-Functional → 0
  
  Age_Factor = min(30, (Age_Days / 1095) × 30)
    - 1095 days = 3 years
    - Max contribution: 30 points
  
  Issue_Factor = min(40, Issue_Count × 5)
    - Each issue adds 5 points
    - Max contribution: 40 points
  
  Degradation_Factor = min(30, Recent_Changes × 10)
    - Recent_Changes = condition changes in last 90 days
    - Each change adds 10 points
    - Max contribution: 30 points
```

### Example Calculation
```
Asset: Desktop Computer
  Condition: Fair → 50 points
  Age: 730 days (2 years)
  Issues: 3 tickets
  Recent Changes: 2 (last 90 days)

Base_Risk = 100 - 50 = 50

Age_Factor = min(30, (730/1095) × 30)
           = min(30, 0.667 × 30)
           = min(30, 20)
           = 20

Issue_Factor = min(40, 3 × 5)
             = min(40, 15)
             = 15

Degradation_Factor = min(30, 2 × 10)
                   = min(30, 20)
                   = 20

Risk Score = 50 + 20 + 15 + 20 = 105
           = Clamped to max 100
           
Final Risk Score: 100 (Critical)
```

### Risk Categories
```
Score ≥ 70  → Critical (🔴)
Score ≥ 50  → High     (🟠)
Score ≥ 30  → Medium   (🟡)
Score < 30  → Low      (🟢)
```

---

## 📈 Prediction Formula

### Single Point Prediction
```
y_predicted = m × x_future + b

Example (using y = -8.3x + 104.4):
  Predict month 10:
  y₁₀ = -8.3 × 10 + 104.4
  y₁₀ = -83 + 104.4
  y₁₀ = 21.4
```

### Multiple Predictions
```
For next 6 months (if last data point is x=12):
  
  Month 13: y₁₃ = m×13 + b
  Month 14: y₁₄ = m×14 + b
  Month 15: y₁₅ = m×15 + b
  Month 16: y₁₆ = m×16 + b
  Month 17: y₁₇ = m×17 + b
  Month 18: y₁₈ = m×18 + b
```

---

## 🔢 Summary Statistics

### Mean (Average)
```
     Σyᵢ
ȳ = ─────
      n
```

### Variance
```
         Σ(yᵢ - ȳ)²
Var(y) = ───────────
            n-1
```

### Standard Deviation
```
σ = √Var(y)
```

### Correlation Coefficient (r)
```
r = √R²   (if slope is positive)
r = -√R²  (if slope is negative)

Range: -1 ≤ r ≤ 1
  r = 1:  Perfect positive correlation
  r = -1: Perfect negative correlation
  r = 0:  No correlation
```

---

## 📝 Quick Reference Table

| Symbol | Meaning |
|--------|---------|
| m | Slope (rate of change) |
| b | Y-intercept (starting value) |
| R² | Coefficient of determination (model fit) |
| n | Number of data points |
| Σ | Sum of all values |
| xᵢ | Individual x value |
| yᵢ | Individual y value |
| ŷᵢ | Predicted y value |
| ȳ | Mean of y values |
| SS_res | Sum of squared residuals |
| SS_tot | Total sum of squares |

---

## 💡 Interpretation Guide

### Slope (m)
- **m > 0**: Positive trend (improving)
- **m < 0**: Negative trend (degrading)
- **m ≈ 0**: No clear trend (stable)
- **|m|** = magnitude of change per time unit

### R² Score
- **0.9-1.0**: Excellent fit
- **0.7-0.9**: Good fit
- **0.5-0.7**: Moderate fit
- **0.3-0.5**: Weak fit
- **0.0-0.3**: Very weak fit

### Confidence in Predictions
```
High Confidence: R² > 0.8 AND n > 10
Medium Confidence: R² > 0.6 AND n > 6
Low Confidence: R² < 0.6 OR n < 6
```

---

## 🎓 Mathematical Properties

### Least Squares Property
The regression line minimizes the sum of squared vertical distances from points to the line.

### Why Square the Residuals?
- Prevents positive and negative errors from canceling
- Penalizes larger errors more heavily
- Mathematically tractable (has a unique solution)

### Assumptions
1. Linear relationship between x and y
2. Independence of observations
3. Homoscedasticity (constant variance)
4. Normal distribution of residuals (for inference)

---

**Note**: All formulas implemented in `controller/get_predictive_analytics.php`

**References**:
- Statistics textbooks
- Linear algebra resources
- Machine learning fundamentals
