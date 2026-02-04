"""
BMI Calculator with Size Recommendation
Calculates BMI and recommends clothing sizes based on gender, height, and weight
"""


class BMICalculator:
    def __init__(self, gender, height_cm, weight_kg):
        # Initialize user attributes and placeholders for computed values
        self.gender = gender
        self.height_cm = height_cm
        self.weight_kg = weight_kg
        self.bmi = 0
        self.category = ""
        self.recommended_size = ""

    def calculate_bmi(self):
        # Convert height to meters and calculate BMI value
        height_m = self.height_cm / 100
        self.bmi = self.weight_kg / (height_m ** 2)
        return round(self.bmi, 2)

    def get_bmi_category(self):
        # Classify BMI into standard health categories
        if self.bmi < 18.5:
            self.category = "Underweight"
        elif self.bmi < 25:
            self.category = "Normal weight"
        elif self.bmi < 30:
            self.category = "Overweight"
        else:
            self.category = "Obese"
        return self.category

    def recommend_size(self):
        # Determine clothing size based on gender, height, and BMI thresholds
        if self.gender.lower() == 'male':
            if self.height_cm < 165:
                self.recommended_size = 'XS' if self.bmi < 20 else ('S' if self.bmi < 23 else ('M' if self.bmi < 27 else 'L'))
            elif self.height_cm < 175:
                self.recommended_size = 'S' if self.bmi < 22 else ('M' if self.bmi < 25 else ('L' if self.bmi < 28 else 'XL'))
            elif self.height_cm < 185:
                self.recommended_size = 'M' if self.bmi < 23 else ('L' if self.bmi < 26 else ('XL' if self.bmi < 29 else 'XXL'))
            else:
                self.recommended_size = 'L' if self.bmi < 24 else ('XL' if self.bmi < 27 else 'XXL')
        else:
            if self.height_cm < 155:
                self.recommended_size = 'XS' if self.bmi < 20 else ('S' if self.bmi < 23 else ('M' if self.bmi < 27 else 'L'))
            elif self.height_cm < 165:
                self.recommended_size = 'S' if self.bmi < 21 else ('M' if self.bmi < 24 else ('L' if self.bmi < 28 else 'XL'))
            elif self.height_cm < 175:
                self.recommended_size = 'M' if self.bmi < 22 else ('L' if self.bmi < 25 else ('XL' if self.bmi < 29 else 'XXL'))
            else:
                self.recommended_size = 'L' if self.bmi < 23 else ('XL' if self.bmi < 26 else 'XXL')

        return self.recommended_size

    def get_full_report(self):
        # Run full pipeline: calculate BMI, classify, and choose size
        self.calculate_bmi()
        self.get_bmi_category()
        self.recommend_size()

        # Return a structured summary of all relevant data
        return {
            'gender': self.gender,
            'height_cm': self.height_cm,
            'weight_kg': self.weight_kg,
            'bmi': round(self.bmi, 2),
            'category': self.category,
            'recommended_size': self.recommended_size
        }


def main():
    # Print header for CLI interaction
    print("=" * 50)
    print("BMI CALCULATOR & SIZE RECOMMENDATION")
    print("=" * 50)
    print()

    # Collect gender, height, and weight input from the user
    gender = input("Enter gender (Male/Female): ").strip()
    height_cm = float(input("Enter height in cm: "))
    weight_kg = float(input("Enter weight in kg: "))

    print()

    # Create calculator instance and generate full report
    calculator = BMICalculator(gender, height_cm, weight_kg)
    report = calculator.get_full_report()

    # Display computed BMI, category, and recommended size
    print("=" * 50)
    print("RESULTS")
    print("=" * 50)
    print(f"Gender: {report['gender']}")
    print(f"Height: {report['height_cm']} cm")
    print(f"Weight: {report['weight_kg']} kg")
    print(f"BMI: {report['bmi']}")
    print(f"Category: {report['category']}")
    print(f"Recommended Size: {report['recommended_size']}")
    print("=" * 50)
    print()
    print(f"Based on your measurements, we recommend size {report['recommended_size']}")
    print(f"for the best fit in our {report['gender']} collection.")
    print()


if __name__ == "__main__":
    # Only run the interactive CLI if this file is executed directly
    main()
