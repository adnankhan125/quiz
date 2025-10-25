<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuizController;

Route::get('/', [UserController::class, 'showForm'])->name('user.form');
Route::post('/user/create', [UserController::class, 'store'])->name('user.create');

Route::get('/quiz/question/{index?}', [QuizController::class, 'getQuestion'])->name('quiz.question');
Route::post('/quiz/answer', [QuizController::class, 'submitAnswer'])->name('quiz.answer');
