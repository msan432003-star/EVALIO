<?php
/**
 * Grader Class
 * Handles multi-question type grading (auto-grading and manual essay evaluation),
 * scoring calculations, answer persistence, and result aggregation.
 */

class Grader
{
    private $_dao;

    public function __construct()
    {
        $this->_dao = new DataAccess();
    }

    /**
     * Normalizes a text string for fuzzy comparison
     */
    public static function normalizeText($text)
    {
        $text = (string)$text;
        $text = trim($text);
        $text = mb_strtolower($text, 'UTF-8');
        // Replace multiple whitespace/newlines with single space
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim standard surrounding punctuation like quotes, period, exclamation, question mark
        $text = trim($text, " \t\n\r\0\x0B\"'.,!?;:");
        return $text;
    }

    /**
     * Evaluates whether a student answer matches the expected answer variants for short_answer / fill_blank
     */
    public static function matchTextAnswer($student_answer, $correct_answer_raw)
    {
        $student_clean = self::normalizeText($student_answer);
        if ($student_clean === '') {
            return false;
        }

        // Support comma, semicolon, pipe, or newline separated alternative answers
        $variants = preg_split('/[,\|\;\n\r]+/', (string)$correct_answer_raw);
        foreach ($variants as $variant) {
            $variant_clean = self::normalizeText($variant);
            if ($variant_clean === '') {
                continue;
            }
            if ($student_clean === $variant_clean) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluates a True/False answer
     */
    public static function matchTrueFalse($student_answer, $correct_answer_raw)
    {
        $s = strtolower(trim((string)$student_answer));
        $c = strtolower(trim((string)$correct_answer_raw));

        $s_bool = in_array($s, array('true', 't', '1', 'yes', 'y'), true);
        $s_is_false = in_array($s, array('false', 'f', '0', 'no', 'n'), true);

        $c_bool = in_array($c, array('true', 't', '1', 'yes', 'y'), true);
        $c_is_false = in_array($c, array('false', 'f', '0', 'no', 'n'), true);

        if ($s_bool && $c_bool) return true;
        if ($s_is_false && $c_is_false) return true;

        return ($s !== '' && $s === $c);
    }

    /**
     * Parses matching pairs structure from question options_data / correct_answer
     */
    public static function parseMatchingPairs($options_data, $correct_answer = null)
    {
        $pairs = array();
        // Try parsing options_data JSON first
        if (!empty($options_data)) {
            $decoded = json_decode($options_data, true);
            if (is_array($decoded)) {
                if (isset($decoded['pairs']) && is_array($decoded['pairs'])) {
                    $pairs = $decoded['pairs'];
                } elseif (isset($decoded[0]['left'])) {
                    $pairs = $decoded;
                }
            }
        }

        // Fallback to correct_answer JSON if options_data wasn't JSON
        if (empty($pairs) && !empty($correct_answer)) {
            $decoded = json_decode($correct_answer, true);
            if (is_array($decoded)) {
                if (isset($decoded['pairs']) && is_array($decoded['pairs'])) {
                    $pairs = $decoded['pairs'];
                } else {
                    foreach ($decoded as $k => $v) {
                        $pairs[] = array('left' => $k, 'right' => $v);
                    }
                }
            }
        }

        return $pairs;
    }

    /**
     * Grades all submitted answers for an exam attempt
     *
     * @param int $exam_id
     * @param int $student_id
     * @param array $submitted_answers Associative array of [question_id => answer_value]
     * @return array Result summary
     */
    public function gradeExamSubmission($exam_id, $student_id, $submitted_answers)
    {
        $exam_id = (int)$exam_id;
        $student_id = (int)$student_id;
        $submitted_answers = is_array($submitted_answers) ? $submitted_answers : array();

        $questions = $this->_dao->getData('*', 'questions', 'exam_id = ' . $exam_id . ' ORDER BY id ASC');
        if (empty($questions)) {
            return array('success' => false, 'error' => 'No questions found for this exam.');
        }

        $total_exam_marks = 0.00;
        $marks_obtained = 0.00;
        $auto_graded_marks = 0.00;
        $correct_count = 0;
        $has_pending_essay = false;
        $total_questions = count($questions);

        // Delete any existing answers for this exam attempt if retaking
        $this->_dao->delete('student_answers', 'exam_id = ' . $exam_id . ' AND student_id = ' . $student_id);

        foreach ($questions as $q) {
            $qid = (int)$q['id'];
            $q_type = !empty($q['question_type']) ? strtolower(trim($q['question_type'])) : 'mcq';
            $max_marks = isset($q['marks']) && (float)$q['marks'] > 0 ? (float)$q['marks'] : 1.00;
            $total_exam_marks += $max_marks;

            $raw_answer = isset($submitted_answers[$qid]) ? $submitted_answers[$qid] : null;

            $is_correct = null;
            $marks_awarded = null;
            $status = 'auto_graded';
            $answer_stored = '';

            switch ($q_type) {
                case 'mcq':
                    $answer_stored = is_scalar($raw_answer) ? trim((string)$raw_answer) : '';
                    $correct_opt = strtoupper(trim((string)($q['correct_option'] ?? '')));
                    if ($answer_stored !== '' && strtoupper($answer_stored) === $correct_opt) {
                        $is_correct = 1;
                        $marks_awarded = $max_marks;
                        $correct_count++;
                    } else {
                        $is_correct = 0;
                        $marks_awarded = 0.00;
                    }
                    $auto_graded_marks += $marks_awarded;
                    break;

                case 'true_false':
                    $answer_stored = is_scalar($raw_answer) ? trim((string)$raw_answer) : '';
                    $correct_raw = !empty($q['correct_answer']) ? $q['correct_answer'] : ($q['correct_option'] ?? '');
                    if ($answer_stored !== '' && self::matchTrueFalse($answer_stored, $correct_raw)) {
                        $is_correct = 1;
                        $marks_awarded = $max_marks;
                        $correct_count++;
                    } else {
                        $is_correct = 0;
                        $marks_awarded = 0.00;
                    }
                    $auto_graded_marks += $marks_awarded;
                    break;

                case 'short_answer':
                    $answer_stored = is_scalar($raw_answer) ? trim((string)$raw_answer) : '';
                    $correct_raw = !empty($q['correct_answer']) ? $q['correct_answer'] : ($q['option_a'] ?? '');
                    if ($answer_stored !== '' && self::matchTextAnswer($answer_stored, $correct_raw)) {
                        $is_correct = 1;
                        $marks_awarded = $max_marks;
                        $correct_count++;
                    } else {
                        $is_correct = 0;
                        $marks_awarded = 0.00;
                    }
                    $auto_graded_marks += $marks_awarded;
                    break;

                case 'fill_blank':
                    $answer_stored = is_scalar($raw_answer) ? trim((string)$raw_answer) : '';
                    $correct_raw = !empty($q['correct_answer']) ? $q['correct_answer'] : ($q['option_a'] ?? '');
                    if ($answer_stored !== '' && self::matchTextAnswer($answer_stored, $correct_raw)) {
                        $is_correct = 1;
                        $marks_awarded = $max_marks;
                        $correct_count++;
                    } else {
                        $is_correct = 0;
                        $marks_awarded = 0.00;
                    }
                    $auto_graded_marks += $marks_awarded;
                    break;

                case 'matching':
                    $pairs = self::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                    $total_pairs = count($pairs);
                    $matched_count = 0;
                    $answer_map = is_array($raw_answer) ? $raw_answer : array();
                    $answer_stored = json_encode($answer_map, JSON_UNESCAPED_UNICODE);

                    if ($total_pairs > 0) {
                        foreach ($pairs as $idx => $p) {
                            $left_val = $p['left'];
                            $expected_right = self::normalizeText($p['right']);
                            
                            // Check answer submitted by index or by left string key
                            $given_right = '';
                            if (isset($answer_map[$idx])) {
                                $given_right = $answer_map[$idx];
                            } elseif (isset($answer_map[$left_val])) {
                                $given_right = $answer_map[$left_val];
                            }

                            if (self::normalizeText($given_right) === $expected_right) {
                                $matched_count++;
                            }
                        }

                        $marks_awarded = round(($matched_count / $total_pairs) * $max_marks, 2);
                        if ($matched_count === $total_pairs) {
                            $is_correct = 1;
                            $correct_count++;
                        } elseif ($matched_count > 0) {
                            $is_correct = 1; // partial credit
                        } else {
                            $is_correct = 0;
                        }
                    } else {
                        $marks_awarded = 0.00;
                        $is_correct = 0;
                    }
                    $auto_graded_marks += $marks_awarded;
                    break;

                case 'essay':
                    $answer_stored = is_scalar($raw_answer) ? trim((string)$raw_answer) : '';
                    $is_correct = null;
                    $marks_awarded = null; // Will be assigned by examiner
                    $status = 'pending_review';
                    $has_pending_essay = true;
                    break;

                default:
                    $answer_stored = is_scalar($raw_answer) ? trim((string)$raw_answer) : '';
                    $marks_awarded = 0.00;
                    $is_correct = 0;
                    break;
            }

            if ($marks_awarded !== null) {
                $marks_obtained += (float)$marks_awarded;
            }

            // Insert into student_answers
            $answer_data = array(
                'exam_id'       => $exam_id,
                'student_id'    => $student_id,
                'question_id'   => $qid,
                'answer_given'  => $answer_stored,
                'is_correct'    => ($is_correct !== null ? (int)$is_correct : 'NULL'),
                'marks_awarded' => ($marks_awarded !== null ? (float)$marks_awarded : 'NULL'),
                'max_marks'     => $max_marks,
                'status'        => $status,
                'feedback'      => ''
            );

            // Clean NULL strings for DataAccess
            $this->insertStudentAnswer($answer_data);
        }

        // Set grading status
        $grading_status = $has_pending_essay ? 'pending_review' : 'completed';

        // Update / Insert exam_results record
        $this->_dao->delete('exam_results', 'exam_id = ' . $exam_id . ' AND student_id = ' . $student_id);

        $result_data = array(
            'student_id'      => $student_id,
            'exam_id'         => $exam_id,
            'score'           => (int)round($marks_obtained),
            'total_marks'     => (float)$total_exam_marks,
            'marks_obtained'  => (float)$marks_obtained,
            'grading_status'  => $grading_status,
            'total_questions' => $total_questions,
            'submitted_at'    => date('Y-m-d H:i:s')
        );

        $this->_dao->insert($result_data, 'exam_results');

        return array(
            'success'           => true,
            'has_pending_essay' => $has_pending_essay,
            'grading_status'    => $grading_status,
            'total_marks'       => $total_exam_marks,
            'marks_obtained'    => $marks_obtained,
            'auto_graded_marks' => $auto_graded_marks,
            'total_questions'   => $total_questions,
            'correct_count'     => $correct_count
        );
    }

    /**
     * Helper to insert student answer row with proper NULLs
     */
    private function insertStudentAnswer($data)
    {
        $is_correct_val = ($data['is_correct'] === 'NULL' || $data['is_correct'] === null) ? 'NULL' : (int)$data['is_correct'];
        $marks_val = ($data['marks_awarded'] === 'NULL' || $data['marks_awarded'] === null) ? 'NULL' : (float)$data['marks_awarded'];
        
        $sql = "INSERT INTO student_answers (exam_id, student_id, question_id, answer_given, is_correct, marks_awarded, max_marks, status, feedback, submitted_at) 
                VALUES (
                    " . (int)$data['exam_id'] . ", 
                    " . (int)$data['student_id'] . ", 
                    " . (int)$data['question_id'] . ", 
                    '" . addslashes((string)$data['answer_given']) . "', 
                    " . $is_correct_val . ", 
                    " . $marks_val . ", 
                    " . (float)$data['max_marks'] . ", 
                    '" . addslashes((string)$data['status']) . "', 
                    '" . addslashes((string)$data['feedback']) . "', 
                    NOW()
                )";
        
        $this->_dao->query($sql);
    }

    /**
     * Examiner grades or updates an essay question mark & feedback
     */
    public function gradeEssayAnswer($answer_id, $marks_awarded, $feedback, $examiner_id)
    {
        $answer_id = (int)$answer_id;
        $examiner_id = (int)$examiner_id;
        $feedback = trim((string)$feedback);

        $ans = $this->_dao->getData('*', 'student_answers', 'id = ' . $answer_id);
        if (empty($ans)) {
            return array('success' => false, 'error' => 'Answer record not found.');
        }

        $row = $ans[0];

        // Authorization check: ensure examiner owns the exam
        if ($examiner_id > 0) {
            $examCheck = $this->_dao->getData(array('id', 'created_by'), 'exams', 'id = ' . (int)$row['exam_id']);
            if (empty($examCheck) || (int)$examCheck[0]['created_by'] !== $examiner_id) {
                return array('success' => false, 'error' => 'Unauthorized: You can only grade exams you created.');
            }
        }
        $max_marks = (float)$row['max_marks'];
        $marks_awarded = (float)$marks_awarded;

        if ($marks_awarded < 0) {
            $marks_awarded = 0.00;
        } elseif ($marks_awarded > $max_marks) {
            $marks_awarded = $max_marks;
        }

        $is_correct = ($marks_awarded >= ($max_marks * 0.5)) ? 1 : 0;

        $sql = "UPDATE student_answers SET 
                    marks_awarded = " . $marks_awarded . ", 
                    is_correct = " . $is_correct . ", 
                    status = 'manually_graded', 
                    feedback = '" . addslashes($feedback) . "', 
                    graded_by = " . $examiner_id . ", 
                    graded_at = NOW() 
                WHERE id = " . $answer_id;

        $this->_dao->query($sql);

        // Recalculate full exam results
        $this->recalculateExamResult((int)$row['exam_id'], (int)$row['student_id'], $examiner_id);

        return array('success' => true, 'marks_awarded' => $marks_awarded);
    }

    /**
     * Examiner overrides an auto-graded or previous mark with audit trail
     */
    public function overrideAnswerGrade($answer_id, $marks_awarded, $feedback, $examiner_id)
    {
        return $this->gradeEssayAnswer($answer_id, $marks_awarded, $feedback, $examiner_id);
    }

    /**
     * Recalculates total exam marks and status for a student attempt
     */
    public function recalculateExamResult($exam_id, $student_id, $examiner_id = null)
    {
        $exam_id = (int)$exam_id;
        $student_id = (int)$student_id;

        $answers = $this->_dao->getData('*', 'student_answers', 'exam_id = ' . $exam_id . ' AND student_id = ' . $student_id);
        if (empty($answers)) {
            return;
        }

        $total_marks = 0.00;
        $marks_obtained = 0.00;
        $has_pending = false;

        foreach ($answers as $a) {
            $total_marks += (float)$a['max_marks'];
            if ($a['status'] === 'pending_review' || $a['marks_awarded'] === null) {
                $has_pending = true;
            } else {
                $marks_obtained += (float)$a['marks_awarded'];
            }
        }

        $grading_status = $has_pending ? 'pending_review' : 'completed';
        $score = (int)round($marks_obtained);
        $eval_by_sql = ($examiner_id !== null && !$has_pending) ? ", evaluated_by = " . (int)$examiner_id . ", evaluated_at = NOW()" : "";

        $sql = "UPDATE exam_results SET 
                    total_marks = " . $total_marks . ", 
                    marks_obtained = " . $marks_obtained . ", 
                    score = " . $score . ", 
                    grading_status = '" . $grading_status . "'
                    " . $eval_by_sql . "
                WHERE exam_id = " . $exam_id . " AND student_id = " . $student_id;

        $this->_dao->query($sql);
    }

    /**
     * Returns full submission details (exam, student, questions + answers) for grading view
     */
    public function getSubmissionDetails($exam_id, $student_id)
    {
        $exam_id = (int)$exam_id;
        $student_id = (int)$student_id;

        $exam = $this->_dao->getData('*', 'exams', 'id = ' . $exam_id);
        $student = $this->_dao->getData('*', 'examinees', 'id = ' . $student_id);
        $result = $this->_dao->getData('*', 'exam_results', 'exam_id = ' . $exam_id . ' AND student_id = ' . $student_id);
        $questions = $this->_dao->getData('*', 'questions', 'exam_id = ' . $exam_id . ' ORDER BY id ASC');
        $answers = $this->_dao->getData('*', 'student_answers', 'exam_id = ' . $exam_id . ' AND student_id = ' . $student_id);

        $answers_by_qid = array();
        if (!empty($answers)) {
            foreach ($answers as $a) {
                $answers_by_qid[(int)$a['question_id']] = $a;
            }
        }

        return array(
            'exam'      => !empty($exam) ? $exam[0] : null,
            'student'   => !empty($student) ? $student[0] : null,
            'result'    => !empty($result) ? $result[0] : null,
            'questions' => !empty($questions) ? $questions : array(),
            'answers'   => $answers_by_qid
        );
    }
}
?>
