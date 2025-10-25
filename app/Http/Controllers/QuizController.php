<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Answer;
use App\Models\UserResult;

class QuizController extends Controller
{

     public function getQuestion($index = 0)
    {
        $asked = session('asked_questions', []);
        $totalToAsk = 5;

        if (count($asked) >= $totalToAsk) {
            $stats = $this->calculateStats();
            return response()->json(['end' => true, 'stats' => $stats]);
        }

        $question = Question::whereNotIn('id', $asked)->inRandomOrder()->first();

        if (!$question) {
            $stats = $this->calculateStats();
            return response()->json(['end' => true, 'stats' => $stats]);
        }

        $answers = $question->answers()->inRandomOrder()->get(['id', 'text']);

        $asked[] = $question->id;
        session(['asked_questions' => $asked]);

        return response()->json([
            'question' => ['id' => $question->id, 'text' => $question->text],
            'answers' => $answers,
        ]);
    }

     public function submitAnswer(Request $request)
    {

         // 👇 debugging purpose
    if (!$request->session()->isStarted()) {
        \Log::info('Session not started at submitAnswer');
    }

   

         
        $stats = session('quiz_stats', ['correct' => 0, 'wrong' => 0, 'skipped' => 0]);

        $question = Question::with('answers')->find($request->question_id);

         UserResult::create([
            'user_id' => $request->user_id,
            'question_id' => $request->question_id,
            'answer_id' => $request->answer_id,
        ]);

        if ($request->action === 'skip') {
            $stats['skipped']++;
        } else {
            $correct = $question->answers->where('is_correct', 1)->first();
            if ($correct && $correct->id == $request->answer_id) {
                $stats['correct']++;
            } else {
                $stats['wrong']++;
            }
        }

        session(['quiz_stats' => $stats]);

        $asked = session('asked_questions', []);
        if (count($asked) >= 5) {
            return response()->json(['end' => true, 'stats' => $stats]);
        }

        return response()->json(['next' => true]);
    }

    // ✅ Helper: Calculate final stats from user_result table
    private function calculateStats()
    {
        $userId = session('user_id');
        $results = UserResult::where('user_id', $userId)->with(['question.answers'])->get();

        $correct = 0;
        $wrong = 0;
        $skipped = 0;

        foreach ($results as $r) {
            if (!$r->answer_id) {
                $skipped++;
            } else {
                $correctAnswer = $r->question->answers->where('is_correct', 1)->first();
                if ($r->answer_id == $correctAnswer->id) {
                    $correct++;
                } else {
                    $wrong++;
                }
            }
        }

        return [
            'correct' => $correct,
            'wrong' => $wrong,
            'skipped' => $skipped,
        ];
    }
}
