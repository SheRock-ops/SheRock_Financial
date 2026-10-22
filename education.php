<?php
// ============================================
// SHEROCK FINANCIAL - FINANCIAL EDUCATION HUB
// ============================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn financial basics with SheRock Financial's interactive lessons and quiz.">
    <title>Financial Education Hub | SheRock Financial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ============================================
           FINANCIAL EDUCATION HUB
        ============================================ */

        .education-page {
            background: #f5f8fc;
            color: #142d4e;
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
        }

        .education-page * {
            box-sizing: border-box;
        }

        .education-hero {
            background: linear-gradient(120deg, #0b1d33, #24476e);
            color: #ffffff;
            text-align: center;
            padding: 90px 20px;
        }

        .education-hero h1 {
            color: #ffffff;
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.3rem, 5vw, 3.8rem);
            margin: 0 0 20px;
        }

        .education-hero p {
            max-width: 760px;
            margin: 0 auto;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .education-container {
            max-width: 1250px;
            margin: 0 auto;
            padding: 75px 25px;
        }

        .education-section-heading {
            text-align: center;
            margin-bottom: 45px;
        }

        .education-eyebrow {
            color: #9b6b2b;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .education-section-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 3rem);
            margin: 0 0 15px;
            color: #142d4e;
        }

        .education-section-heading p {
            color: #526782;
            line-height: 1.8;
            max-width: 720px;
            margin: 0 auto;
        }

        /* ============================================
           EXPANDABLE LESSONS
        ============================================ */

        .education-lessons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 25px;
            align-items: start;
        }

        .education-lesson {
            background: #ffffff;
            border-top: 4px solid #d7b879;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(20, 45, 78, 0.06);
            overflow: hidden;
        }

        .education-lesson summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 28px;
            cursor: pointer;
            list-style: none;
        }

        .education-lesson summary::-webkit-details-marker {
            display: none;
        }

        .education-lesson summary:focus-visible {
            outline: 3px solid #d7b879;
            outline-offset: -3px;
        }

        .education-lesson h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            color: #142d4e;
            margin: 8px 0;
        }

        .education-lesson-number {
            color: #9b6b2b;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .education-lesson-toggle {
            background: #f5f8fc;
            color: #142d4e;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            font-size: 1.5rem;
            transition: transform 0.2s ease;
        }

        .education-lesson[open] .education-lesson-toggle {
            transform: rotate(45deg);
        }

        .education-lesson-content {
            border-top: 1px solid #e4eaf2;
            padding: 5px 28px 28px;
            color: #526782;
            line-height: 1.85;
        }

        .education-lesson-content p {
            margin: 18px 0 12px;
        }

        .education-lesson-content ul {
            padding-left: 22px;
        }

        .education-lesson-content li {
            margin-bottom: 10px;
        }

        .education-tip {
            background: #f5f8fc;
            border-left: 4px solid #d7b879;
            border-radius: 8px;
            padding: 17px;
            margin-top: 20px;
        }

        /* ============================================
           QUIZ
        ============================================ */

        .education-quiz-section {
            padding: 20px 0 90px;
        }

        #financial-quiz {
            scroll-margin-top: 30px;
        }

        html {
            scroll-behavior: smooth;
        }

        .education-quiz-card {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-top: 5px solid #d7b879;
            border-radius: 16px;
            padding: 45px;
            box-shadow: 0 15px 35px rgba(20, 45, 78, 0.07);
        }

        .education-quiz-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            color: #526782;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .education-progress-track {
            height: 12px;
            border-radius: 20px;
            background: #e5ebf3;
            overflow: hidden;
            margin-bottom: 35px;
        }

        .education-progress-fill {
            height: 100%;
            width: 0;
            background: #d7b879;
            transition: width 0.3s ease;
        }

        .education-quiz-question-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: #142d4e;
            margin-bottom: 25px;
        }

        .education-quiz-options {
            display: grid;
            gap: 14px;
        }

        .education-quiz-option {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            border: 1px solid #d5dfeb;
            border-radius: 10px;
            cursor: pointer;
            color: #142d4e;
            line-height: 1.5;
        }

        .education-quiz-option:hover {
            background: #f5f8fc;
        }

        .education-quiz-option input {
            accent-color: #142d4e;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .education-option-correct {
            border-color: #81bd94;
            background: #e9f6ed;
        }

        .education-option-incorrect {
            border-color: #e4a3a3;
            background: #fff0f0;
        }

        .education-answer-feedback {
            background: #f5f8fc;
            border-left: 4px solid #d7b879;
            padding: 20px;
            margin-top: 25px;
            line-height: 1.7;
            border-radius: 8px;
        }

        .education-answer-feedback p {
            margin-bottom: 0;
        }

        .education-quiz-error {
            color: #b42323;
            min-height: 24px;
            margin: 15px 0;
        }

        .education-button {
            display: inline-block;
            background: #d7b879;
            color: #0b1d33;
            border: none;
            border-radius: 8px;
            padding: 16px 30px;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }

        .education-button:hover {
            background: #c5a461;
        }

        .education-button-navy {
            background: #142d4e;
            color: #ffffff;
        }

        .education-button-navy:hover {
            background: #24476e;
        }

        .education-final-score {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: #142d4e;
            margin: 15px 0;
        }

        .education-quiz-results[hidden],
        .education-button[hidden] {
            display: none;
        }

        .education-quiz-results {
            text-align: center;
        }

        .education-disclaimer {
            background: #eaf0f7;
            border-left: 4px solid #d7b879;
            border-radius: 8px;
            padding: 20px;
            color: #526782;
            line-height: 1.7;
            margin-top: 50px;
        }

        .education-footer {
            background: #0b1d33;
            color: #ffffff;
            border-top: 3px solid #d7b879;
            text-align: center;
            padding: 35px 20px;
            line-height: 1.8;
        }

        .education-footer p {
            margin: 6px 0;
        }

        .education-footer a {
            color: #d7b879;
        }

        /* ============================================
           RESPONSIVE DESIGN
        ============================================ */

        @media screen and (max-width: 800px) {
            .education-lessons {
                grid-template-columns: 1fr;
            }

            .education-container {
                padding: 55px 20px;
            }

            .education-quiz-card {
                padding: 28px;
            }
        }

        @media screen and (max-width: 480px) {
            .education-hero {
                padding: 65px 20px;
            }

            .education-lesson summary {
                padding: 22px;
            }

            .education-lesson-content {
                padding: 5px 22px 22px;
            }

            .education-quiz-card {
                padding: 22px;
            }

            .education-quiz-top {
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .education-progress-fill,
            .education-lesson-toggle {
                transition: none;
            }
        }
    </style>
</head>

<body class="education-page">

    <!-- Shared Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ============================================
         EDUCATION HERO
    ============================================ -->

    <header class="education-hero">
        <h1>Financial Education Hub</h1>
        <p>
            Build your financial confidence, one lesson at a time.
            Learn practical money skills and test your knowledge
            with our interactive financial literacy quiz.
        </p>
    </header>

    <main>

        <!-- ============================================
             FINANCIAL LESSONS
        ============================================ -->

        <section class="education-container" aria-labelledby="lessons-title">

            <div class="education-section-heading">
                <p class="education-eyebrow">Save Smarter. Grow Stronger.</p>
                <h2 id="lessons-title">Explore Financial Lessons</h2>
                <p>
                    Select a lesson to expand it and discover
                    useful financial concepts.
                </p>
            </div>

            <div class="education-lessons">

                <!-- LESSON 01 -->
                <details class="education-lesson">
                    <summary>
                        <div>
                            <span class="education-lesson-number">LESSON 01</span>
                            <h3>Budgeting Basics</h3>
                        </div>
                        <span class="education-lesson-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="education-lesson-content">
                        <p>
                            A budget is a plan for how you will use your income.
                            It helps you understand what you earn, spend, and save.
                        </p>

                        <ul>
                            <li>Track your monthly income.</li>
                            <li>Separate needs from wants.</li>
                            <li>Review recurring expenses.</li>
                            <li>Set aside money for savings.</li>
                        </ul>

                        <div class="education-tip">
                            <strong>SheRock Tip:</strong>
                            The 50/30/20 rule is one possible starting point:
                            50% for needs, 30% for wants, and 20% for
                            savings or debt repayment. Adjust it to your circumstances.
                        </div>
                    </div>
                </details>

                <!-- LESSON 02 -->
                <details class="education-lesson">
                    <summary>
                        <div>
                            <span class="education-lesson-number">LESSON 02</span>
                            <h3>Saving with Purpose</h3>
                        </div>
                        <span class="education-lesson-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="education-lesson-content">
                        <p>
                            Saving means setting money aside for future needs
                            and goals rather than spending it immediately.
                        </p>

                        <ul>
                            <li>Choose a specific savings goal.</li>
                            <li>Decide how much money you need.</li>
                            <li>Set a realistic target date.</li>
                            <li>Contribute consistently when possible.</li>
                        </ul>

                        <div class="education-tip">
                            <strong>SheRock Tip:</strong>
                            Even small, regular contributions can help you
                            develop a strong savings habit.
                        </div>
                    </div>
                </details>

                <!-- LESSON 03 -->
                <details class="education-lesson">
                    <summary>
                        <div>
                            <span class="education-lesson-number">LESSON 03</span>
                            <h3>Emergency Funds</h3>
                        </div>
                        <span class="education-lesson-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="education-lesson-content">
                        <p>
                            An emergency fund is money reserved for unexpected
                            expenses, such as urgent repairs, medical costs,
                            or temporary income loss.
                        </p>

                        <ul>
                            <li>Start with a manageable amount.</li>
                            <li>Keep emergency savings accessible.</li>
                            <li>Use the fund for genuine emergencies.</li>
                            <li>Rebuild it after withdrawals.</li>
                        </ul>

                        <div class="education-tip">
                            <strong>SheRock Tip:</strong>
                            Three to six months of essential expenses is
                            a commonly suggested target, but your ideal
                            amount depends on your circumstances.
                        </div>
                    </div>
                </details>

                <!-- LESSON 04 -->
                <details class="education-lesson">
                    <summary>
                        <div>
                            <span class="education-lesson-number">LESSON 04</span>
                            <h3>Understanding Interest</h3>
                        </div>
                        <span class="education-lesson-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="education-lesson-content">
                        <p>
                            Interest is money earned on savings or investments,
                            or money paid when borrowing.
                        </p>

                        <ul>
                            <li>Simple interest is calculated on the principal.</li>
                            <li>Compound interest can earn interest on earlier interest.</li>
                            <li>Higher rates may increase returns or borrowing costs.</li>
                            <li>Compounding frequency affects calculations.</li>
                        </ul>

                        <div class="education-tip">
                            <strong>Example:</strong>
                            $1,000 earning 3% annually would grow to
                            approximately $1,030 after one year,
                            assuming no fees or withdrawals.
                        </div>
                    </div>
                </details>

                <!-- LESSON 05 -->
                <details class="education-lesson">
                    <summary>
                        <div>
                            <span class="education-lesson-number">LESSON 05</span>
                            <h3>Investment Fundamentals</h3>
                        </div>
                        <span class="education-lesson-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="education-lesson-content">
                        <p>
                            Investing involves putting money into financial
                            assets with the aim of generating income or
                            increasing value over time.
                        </p>

                        <ul>
                            <li>Understand the risks before investing.</li>
                            <li>Consider your financial goals and timeline.</li>
                            <li>Compare fees, liquidity, and potential returns.</li>
                            <li>Learn about diversification.</li>
                        </ul>

                        <div class="education-tip">
                            <strong>Canadian Finance Tip:</strong>
                            A TFSA and an RRSP are account types with
                            different tax rules. The investments held
                            inside them determine their actual returns.
                        </div>
                    </div>
                </details>

                <!-- LESSON 06 -->
                <details class="education-lesson">
                    <summary>
                        <div>
                            <span class="education-lesson-number">LESSON 06</span>
                            <h3>Financial Security</h3>
                        </div>
                        <span class="education-lesson-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="education-lesson-content">
                        <p>
                            Financial security means having a plan to manage
                            your money, prepare for setbacks, and work
                            toward long-term stability.
                        </p>

                        <ul>
                            <li>Build healthy saving habits.</li>
                            <li>Review your financial goals regularly.</li>
                            <li>Protect personal banking information.</li>
                            <li>Be cautious of guaranteed high-return promises.</li>
                        </ul>

                        <div class="education-tip">
                            <strong>SheRock Tip:</strong>
                            Financial confidence grows through knowledge,
                            consistent habits, and informed decisions.
                        </div>
                    </div>
                </details>

            </div>
        </section>

        <!-- ============================================
             INTERACTIVE FINANCIAL QUIZ
        ============================================ -->

        <section
            id="financial-quiz"
            class="education-quiz-section"
            aria-labelledby="quiz-title"
        >

            <div class="education-container">

                <div class="education-section-heading">
                    <p class="education-eyebrow">Test Your Knowledge</p>
                    <h2 id="quiz-title">Financial Literacy Quiz</h2>
                    <p>
                        Answer six questions to see how much you have learned.
                    </p>
                </div>

                <div class="education-quiz-card">

                    <div class="education-quiz-top">
                        <span id="education-question-counter">Question 1 of 6</span>
                        <span id="education-progress-label">0% complete</span>
                    </div>

                    <div
                        id="education-progress-track"
                        class="education-progress-track"
                        role="progressbar"
                        aria-label="Quiz progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                    >
                        <div
                            id="education-progress-fill"
                            class="education-progress-fill"
                        ></div>
                    </div>

                    <div id="education-question-container"></div>

                    <p
                        id="education-quiz-error"
                        class="education-quiz-error"
                        role="alert"
                    ></p>

                    <button
                        type="button"
                        id="education-next-button"
                        class="education-button"
                    >
                        Check Answer
                    </button>

                    <div
                        id="education-results-container"
                        class="education-quiz-results"
                        aria-live="polite"
                        hidden
                    ></div>

                </div>

                <div class="education-disclaimer">
                    <strong>Educational Disclaimer:</strong>
                    SheRock Financial provides financial education
                    and planning tools for informational purposes only.
                    Content is not personalized financial or investment advice.
                </div>

            </div>
        </section>

    </main>

    <!-- ============================================
         FOOTER
    ============================================ -->

    <footer class="education-footer">
        <p>&copy; <?php echo date('Y'); ?> SheRock Financial. All rights reserved.</p>
        <p>Save Smarter. Grow Stronger.</p>
        <p><a href="dashboard.php">Return to Dashboard</a></p>
    </footer>

    <!-- ============================================
         FINANCIAL EDUCATION QUIZ JAVASCRIPT
    ============================================ -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const educationQuestions = [
                {
                    question: 'What is the main purpose of a budget?',
                    options: [
                        'To spend all your income',
                        'To plan and manage your money',
                        'To avoid saving money',
                        'To guarantee investment returns'
                    ],
                    answer: 1,
                    explanation: 'A budget helps you plan income, expenses, and savings.'
                },
                {
                    question: 'Why is an emergency fund important?',
                    options: [
                        'It guarantees investment profits',
                        'It replaces a monthly budget',
                        'It helps cover unexpected expenses',
                        'It eliminates all financial risks'
                    ],
                    answer: 2,
                    explanation: 'Emergency savings provide a financial cushion when unexpected costs arise.'
                },
                {
                    question: 'What does compound interest mean?',
                    options: [
                        'Interest earned only on the original amount',
                        'Interest earned on principal and previously earned interest',
                        'A fixed monthly banking fee',
                        'Money that cannot earn interest'
                    ],
                    answer: 1,
                    explanation: 'Compound interest allows previously earned interest to generate additional interest.'
                },
                {
                    question: 'Which statement about investing is correct?',
                    options: [
                        'Every investment guarantees a profit',
                        'Higher potential returns can involve greater risk',
                        'Investments never lose value',
                        'All investments have identical risks'
                    ],
                    answer: 1,
                    explanation: 'Investments involve different levels of risk, and returns are not always guaranteed.'
                },
                {
                    question: 'What is a TFSA in Canada?',
                    options: [
                        'A type of credit card',
                        'A guaranteed fixed-rate investment',
                        'A tax-advantaged savings and investment account',
                        'A personal loan'
                    ],
                    answer: 2,
                    explanation: 'A TFSA is an account with specific Canadian tax advantages. Its returns depend on what it holds.'
                },
                {
                    question: 'Which habit supports long-term financial confidence?',
                    options: [
                        'Ignoring financial statements',
                        'Borrowing without understanding the terms',
                        'Making decisions based only on advertisements',
                        'Reviewing goals and saving consistently'
                    ],
                    answer: 3,
                    explanation: 'Regular financial reviews and consistent savings can support long-term progress.'
                }
            ];

            const questionCounter = document.getElementById('education-question-counter');
            const progressLabel = document.getElementById('education-progress-label');
            const progressTrack = document.getElementById('education-progress-track');
            const progressFill = document.getElementById('education-progress-fill');
            const questionContainer = document.getElementById('education-question-container');
            const quizError = document.getElementById('education-quiz-error');
            const nextButton = document.getElementById('education-next-button');
            const resultsContainer = document.getElementById('education-results-container');

            let currentQuestion = 0;
            let quizScore = 0;
            let answerSubmitted = false;

            // ============================================
            // DISPLAY CURRENT QUESTION
            // ============================================

            function renderEducationQuestion() {
                answerSubmitted = false;
                quizError.textContent = '';
                nextButton.textContent = 'Check Answer';

                const item = educationQuestions[currentQuestion];
                const percent = Math.round(
                    (currentQuestion / educationQuestions.length) * 100
                );

                questionCounter.textContent =
                    `Question ${currentQuestion + 1} of ${educationQuestions.length}`;

                progressLabel.textContent = `${percent}% complete`;
                progressFill.style.width = `${percent}%`;
                progressTrack.setAttribute('aria-valuenow', String(percent));

                questionContainer.replaceChildren();

                const heading = document.createElement('h3');
                heading.className = 'education-quiz-question-title';
                heading.textContent = item.question;
                questionContainer.appendChild(heading);

                const options = document.createElement('div');
                options.className = 'education-quiz-options';

                item.options.forEach((option, index) => {
                    const label = document.createElement('label');
                    label.className = 'education-quiz-option';

                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'education-answer';
                    radio.value = String(index);

                    const text = document.createElement('span');
                    text.textContent = option;

                    label.append(radio, text);
                    options.appendChild(label);
                });

                questionContainer.appendChild(options);
            }

            // ============================================
            // DISPLAY FINAL QUIZ RESULTS
            // ============================================

            function showEducationResults() {
                questionContainer.replaceChildren();
                quizError.textContent = '';

                questionCounter.textContent = 'Quiz Complete';
                progressLabel.textContent = '100% complete';
                progressFill.style.width = '100%';
                progressTrack.setAttribute('aria-valuenow', '100');

                nextButton.hidden = true;
                resultsContainer.hidden = false;
                resultsContainer.replaceChildren();

                const title = document.createElement('h3');
                title.textContent = 'Your Quiz Results';

                const score = document.createElement('p');
                score.className = 'education-final-score';
                score.textContent = `${quizScore} / ${educationQuestions.length}`;

                const message = document.createElement('p');
                message.textContent =
                    quizScore === educationQuestions.length
                        ? 'Excellent work! You have a strong grasp of these financial basics.'
                        : 'Great effort! Review the lessons above and keep building your confidence.';

                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'education-button education-button-navy';
                retry.textContent = 'Try Quiz Again';

                retry.addEventListener('click', () => {
                    currentQuestion = 0;
                    quizScore = 0;

                    nextButton.hidden = false;
                    resultsContainer.hidden = true;

                    renderEducationQuestion();
                });

                resultsContainer.append(title, score, message, retry);
            }

            // ============================================
            // CHECK ANSWER AND MOVE TO NEXT QUESTION
            // ============================================

            nextButton.addEventListener('click', () => {

                if (answerSubmitted) {
                    currentQuestion++;

                    if (currentQuestion >= educationQuestions.length) {
                        showEducationResults();
                    } else {
                        renderEducationQuestion();
                    }

                    return;
                }

                const selected = questionContainer.querySelector(
                    'input[name="education-answer"]:checked'
                );

                if (!selected) {
                    quizError.textContent =
                        'Please select an answer before continuing.';
                    return;
                }

                quizError.textContent = '';

                const item = educationQuestions[currentQuestion];
                const selectedIndex = Number(selected.value);
                const isCorrect = selectedIndex === item.answer;

                if (isCorrect) {
                    quizScore++;
                }

                answerSubmitted = true;

                questionContainer
                    .querySelectorAll('.education-quiz-option')
                    .forEach((label, index) => {

                        const input = label.querySelector('input');
                        input.disabled = true;

                        if (index === item.answer) {
                            label.classList.add('education-option-correct');
                        }

                        if (index === selectedIndex && !isCorrect) {
                            label.classList.add('education-option-incorrect');
                        }
                    });

                const feedback = document.createElement('div');
                feedback.className = 'education-answer-feedback';

                const strong = document.createElement('strong');
                strong.textContent = isCorrect
                    ? 'Correct!'
                    : 'Keep learning!';

                const explanation = document.createElement('p');
                explanation.textContent = item.explanation;

                feedback.append(strong, explanation);
                questionContainer.appendChild(feedback);

                nextButton.textContent =
                    currentQuestion === educationQuestions.length - 1
                        ? 'View Results'
                        : 'Next Question';
            });

            // ============================================
            // INITIALIZE QUIZ
            // ============================================

            renderEducationQuestion();

        });
    </script>

</body>
</html>
