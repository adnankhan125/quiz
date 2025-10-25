<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AJAX Quiz</title>

  {{-- ✅ CSRF token --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- ✅ jQuery --}}
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body { font-family: Arial; background:#f9f9f9; }
    .container { max-width: 500px; margin: 60px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 8px rgba(0,0,0,0.1);}
    .question { font-size: 18px; margin-bottom: 20px; }
    .btn { margin-top:10px; padding:10px 15px; border:none; border-radius:5px; cursor:pointer; }
    .btn-skip { background:#f0ad4e; color:#fff; }
    .btn-next { background:#0275d8; color:#fff; }
  </style>
</head>
<body>
  <div class="container" id="quizApp">
    {{-- Step 1: Name input --}}
    <div id="startScreen">
      <h2>Enter Your Name to Start Quiz</h2>
      <input type="text" id="username" placeholder="Your name..." style="width:100%;padding:10px;margin:10px 0;">
      <button id="startBtn" class="btn btn-next">Start Quiz</button>
      <p id="error" style="color:red;"></p>
    </div>

    {{-- Step 2: Quiz section --}}
    <div id="quizScreen" style="display:none;">
      <div class="question" id="questionText"></div>
      <form id="answerForm">
        <div id="answers"></div>
        <div style="margin-top:15px;">
          <button type="button" class="btn btn-skip" id="skipBtn">Skip</button>
          <button type="button" class="btn btn-next" id="nextBtn">Next</button>
        </div>
      </form>
    </div>

    {{-- Step 3: Result section --}}
    <div id="resultScreen" style="display:none; text-align:center;">
      <h2>Quiz Result</h2>
      <p>Correct: <span id="correctCount"></span></p>
      <p>Wrong: <span id="wrongCount"></span></p>
      <p>Skipped: <span id="skipCount"></span></p>
      <button onclick="window.location.reload()" class="btn btn-next">Restart</button>
    </div>
  </div>

  <script>
    let userId = null;
    let currentQuestionId = null;

    // ✅ Global AJAX setup
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      xhrFields: {
        withCredentials: true // ensure session cookie is sent
      }
    });

    // ✅ Create user
    $('#startBtn').click(function() {
      const username = $('#username').val().trim();
      if (!username) {
        $('#error').text('Please enter your name');
        return;
      }

      $.ajax({
        url: '{{ route("user.create") }}',
        method: 'POST',
        data: { username },
        success: function(res) {
          userId = res.user_id;
          $('#startScreen').hide();
          $('#quizScreen').show();
          loadQuestion();
        },
        error: function(xhr) {
          if (xhr.responseJSON?.error) {
            $('#error').text(xhr.responseJSON.error);
          } else {
            $('#error').text('Something went wrong.');
          }
        }
      });
    });

    // ✅ Load random question
    function loadQuestion() {
      $.get('{{ url("/quiz/question/0") }}', function(res) {
        if (res.end) {
          showResult(res.stats);
          return;
        }

        currentQuestionId = res.question.id;
        $('#questionText').text(res.question.text);

        let answersHtml = '';
        res.answers.forEach(a => {
          answersHtml += `
            <div style="margin-bottom:8px;">
              <label>
                <input type="radio" name="answer" value="${a.id}"> ${a.text}
              </label>
            </div>`;
        });

        $('#answers').html(answersHtml);
      });
    }

    // ✅ Handle Next button
    $('#nextBtn').click(function() {
      const selected = $('input[name="answer"]:checked').val();
      if (!selected) {
        alert('Please select an answer or press Skip.');
        return;
      }
      submitAnswer(selected, 'answer');
    });

    // ✅ Handle Skip button
    $('#skipBtn').click(function() {
      submitAnswer(null, 'skip');
    });

  // ✅ Submit answer (CSRF + session safe)
    function submitAnswer(answerId, action) {
    $.ajax({
        url: '{{ route("quiz.answer") }}',
        method: 'POST',
        data: {
        user_id: userId,
        question_id: currentQuestionId,
        answer_id: answerId,
        action: action
        },
        success: function (res) {
        if (res.end) {
            showResult(res.stats);
        } else {
            loadQuestion();
        }
        },
        error: function (xhr) {
        console.error(xhr.responseText);
        alert('Error submitting answer.');
        }
    });
    }


    // ✅ Show result
    function showResult(stats) {
      $('#quizScreen').hide();
      $('#resultScreen').show();
      $('#correctCount').text(stats.correct);
      $('#wrongCount').text(stats.wrong);
      $('#skipCount').text(stats.skipped);
    }
  </script>
</body>
</html>
