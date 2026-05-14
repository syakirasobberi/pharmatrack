import json
import sys
from pathlib import Path

import pandas as pd
from sklearn.tree import DecisionTreeClassifier


def calculate_realistic_confidence(risk, model_confidence, bmi, blood_sugar, blood_pressure, cholesterol):
    risk_points = 0

    if bmi >= 30:
        risk_points += 2
    elif bmi >= 25:
        risk_points += 1

    if blood_sugar >= 7.0:
        risk_points += 2
    elif blood_sugar >= 5.6:
        risk_points += 1

    if blood_pressure >= 140:
        risk_points += 2
    elif blood_pressure >= 130:
        risk_points += 1

    if cholesterol >= 6.2:
        risk_points += 2
    elif cholesterol >= 5.2:
        risk_points += 1

    if risk == "High":
        clinical_confidence = 78 + min(risk_points * 2, 14)
    elif risk == "Moderate":
        clinical_confidence = 72 + min(risk_points * 2, 14)
    else:
        clinical_confidence = 82 if risk_points == 0 else 76

    blended_confidence = round((model_confidence * 0.55) + (clinical_confidence * 0.45))
    return max(68, min(blended_confidence, 94))


def main():
    if len(sys.argv) != 5:
        print(json.dumps({"error": "Usage: python predict.py bmi blood_sugar blood_pressure cholesterol"}))
        return

    try:
        bmi = float(sys.argv[1])
        blood_sugar = float(sys.argv[2])
        blood_pressure = float(sys.argv[3])
        cholesterol = float(sys.argv[4])
    except ValueError:
        print(json.dumps({"error": "All input values must be numeric"}))
        return

    dataset_path = Path(__file__).with_name("health_data.csv")
    data = pd.read_csv(dataset_path)

    x = data[["BMI", "BloodSugar", "BloodPressure", "Cholesterol"]]
    y = data["Risk"]

    model = DecisionTreeClassifier(max_depth=4, random_state=42)
    model.fit(x, y)

    patient_data = pd.DataFrame([{
        "BMI": bmi,
        "BloodSugar": blood_sugar,
        "BloodPressure": blood_pressure,
        "Cholesterol": cholesterol,
    }])

    risk = model.predict(patient_data)[0]
    probabilities = model.predict_proba(patient_data)[0]
    model_confidence = round(max(probabilities) * 100)
    confidence = calculate_realistic_confidence(
        risk,
        model_confidence,
        bmi,
        blood_sugar,
        blood_pressure,
        cholesterol,
    )

    print(json.dumps({
        "risk": risk,
        "confidence": f"{confidence}%"
    }))


if __name__ == "__main__":
    main()
