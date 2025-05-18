<?php
class GradeCalculator {
    private static $gradeRanges = [
        ['min' => 90, 'max' => 100, 'grade' => 'A', 'point' => 4.00],
        ['min' => 80, 'max' => 89.99, 'grade' => 'A-', 'point' => 3.70],
        ['min' => 70, 'max' => 79.99, 'grade' => 'B+', 'point' => 3.30],
        ['min' => 60, 'max' => 69.99, 'grade' => 'B', 'point' => 3.00],
        ['min' => 50, 'max' => 59.99, 'grade' => 'B-', 'point' => 2.70],
        ['min' => 0, 'max' => 49.99, 'grade' => 'F', 'point' => 0.00]
    ];

    /**
     * Calculate grade and grade point based on marks and max marks
     * @param float $marks The marks obtained
     * @param float $max_marks The maximum marks possible
     * @return array Array containing grade and grade point
     */
    public static function calculate($marks, $max_marks) {
        // Calculate percentage
        $percentage = ($marks / $max_marks) * 100;
        
        foreach (self::$gradeRanges as $range) {
            if ($percentage >= $range['min'] && $percentage <= $range['max']) {
                return [
                    'grade' => $range['grade'],
                    'grade_point' => $range['point']
                ];
            }
        }
        
        // Default to F grade if no range matches (shouldn't happen with proper ranges)
        return ['grade' => 'F', 'grade_point' => 0.00];
    }

    /**
     * Validate marks against maximum marks
     * @param mixed $marks The marks to validate
     * @param float $max_marks The maximum marks possible
     * @return bool True if marks are valid
     */
    public static function validateMarks($marks, $max_marks) {
        return is_numeric($marks) && $marks >= 0 && $marks <= $max_marks;
    }

    /**
     * Get remarks based on grade
     * @param string $grade The grade obtained
     * @return string Appropriate remarks
     */
    public static function getRemarks($grade) {
        switch ($grade) {
            case 'A':
                return 'Distinction';
            case 'A-':
                return 'Very Good';
            case 'B+':
                return 'First Division';
            case 'B':
                return 'Second Division';
            case 'B-':
                return 'Pass';
            default:
                return 'Fail';
        }
    }
} 