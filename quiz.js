class Quiz {
    constructor(quizId, questions, timeLimit) {
        this.quizId = quizId;
        this.questions = questions;
        this.timeLimit = timeLimit;
        this.currentQuestionIndex = 0;
        this.score = 0;
        this.timer = null;
        this.timeRemaining = timeLimit;
        this.startTime = null;
        this.quizTitle = document.title;
        
        this.init();
    }
    
    escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
    
    init() {
        console.log("Initializing quiz...");
        QuizAnalytics.trackQuizStart(this.quizId, this.quizTitle);
        this.startTime = new Date();
        
        // Initialize UI first
        this.updateProgress();
        this.updateTimerDisplay();
        
        // Then display question
        this.displayQuestion();
        this.startTimer();
        
        // Set up event listeners
        document.getElementById('next-btn').addEventListener('click', () => this.nextQuestion());
        document.getElementById('prev-btn').addEventListener('click', () => this.prevQuestion());
        document.getElementById('submit-quiz').addEventListener('click', () => this.submitQuiz());
    }
    
    displayQuestion() {
        console.log("Displaying question..."); // Debug log
        
        const question = this.questions[this.currentQuestionIndex];
        const questionElement = document.getElementById('question');
        const optionsElement = document.getElementById('options');
    
        // Debug current question
        console.log("Current question:", question);
    
        if (!questionElement || !optionsElement) {
            console.error("Question or options element not found!");
            return;
        }
    
        // Clear previous content
        questionElement.innerHTML = '';
        optionsElement.innerHTML = '';
    
        // Add question text
        const questionText = document.createElement('div');
        questionText.className = 'question-text';
        questionText.textContent = question.question_text;
        questionElement.appendChild(questionText);
    
        // Add options
        ['a', 'b', 'c', 'd'].forEach(option => {
            const optionValue = question[`option_${option}`];
            if (!optionValue) {
                console.warn(`Option ${option} is missing!`);
                return;
            }
    
            const optionContainer = document.createElement('div');
            optionContainer.className = 'option';
    
            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.id = `option-${option}`;
            radio.name = 'answer';
            radio.value = option.toUpperCase();
    
            const label = document.createElement('label');
            label.htmlFor = `option-${option}`;
            // Modified line to properly display HTML tags
            label.textContent = `${option.toUpperCase()}: ${optionValue.replace(/&lt;/g, '<').replace(/&gt;/g, '>')}`;
    
            optionContainer.appendChild(radio);
            optionContainer.appendChild(label);
            optionsElement.appendChild(optionContainer);
        });
    
        this.updateProgress();
    }
    
    updateProgress() {
        const progressElement = document.getElementById('progress');
        if (progressElement) {
            progressElement.textContent = 
                `Question ${this.currentQuestionIndex + 1} of ${this.questions.length}`;
        }
        
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        if (prevBtn && nextBtn) {
            prevBtn.disabled = this.currentQuestionIndex === 0;
            nextBtn.disabled = this.currentQuestionIndex === this.questions.length - 1;
        }
    }
    
    startTimer() {
        // Clear existing timer if any
        if (this.timer) {
            clearInterval(this.timer);
        }
        
        this.timer = setInterval(() => {
            this.timeRemaining--;
            this.updateTimerDisplay();
            
            if (this.timeRemaining <= 0) {
                this.timeExpired();
            }
        }, 1000);
    }
    
    updateTimerDisplay() {
        const timerElement = document.getElementById('timer');
        if (timerElement) {
            const minutes = Math.floor(this.timeRemaining / 60);
            const seconds = this.timeRemaining % 60;
            timerElement.textContent = 
                `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        }
    }
    
    timeExpired() {
        clearInterval(this.timer);
        QuizAnalytics.trackTimerExpired(this.quizId, this.quizTitle);
        alert('Time has expired! Your quiz will be submitted automatically.');
        this.submitQuiz();
    }
    
    nextQuestion() {
        this.saveAnswer();
        if (this.currentQuestionIndex < this.questions.length - 1) {
            this.currentQuestionIndex++;
            this.displayQuestion();
            this.updateProgress();
        }
    }
    
    prevQuestion() {
        this.saveAnswer();
        if (this.currentQuestionIndex > 0) {
            this.currentQuestionIndex--;
            this.displayQuestion();
            this.updateProgress();
        }
    }
    
    saveAnswer() {
        const selectedOption = document.querySelector('input[name="answer"]:checked');
        if (selectedOption && this.questions[this.currentQuestionIndex].id) {
            const question = this.questions[this.currentQuestionIndex];
            const isCorrect = selectedOption.value === question.correct_answer;
            const timeTaken = Math.max(1, Math.round((new Date() - this.startTime) / 1000));
            
            // DEBUG: Verify question tracking
            console.log("Tracking answer:", {
                questionId: question.id,
                isCorrect: isCorrect,
                timeTaken: timeTaken
            });
            
            QuizAnalytics.trackQuestionAnswer(
                this.quizId,
                question.id,
                isCorrect,
                timeTaken
            );
            
            if (isCorrect) this.score++;
            this.startTime = new Date();
        }
    }
    
    submitQuiz() {
        clearInterval(this.timer);
        this.saveAnswer();
        
        const timeTaken = Math.max(1, Math.round((new Date() - this.startTime) / 1000));
        
        // DEBUG: Verify data before sending
        console.log("Submitting quiz with:", {
            quizId: this.quizId,
            score: this.score,
            totalQuestions: this.questions.length,
            timeTaken: timeTaken
        });
        
        // Send completion event
        QuizAnalytics.trackQuizComplete(
            this.quizId,
            this.quizTitle,
            this.score,
            this.questions.length,
            timeTaken
        );

        // Redirect after short delay
        setTimeout(() => {
            window.location.href = `results.php?quiz_id=${this.quizId}&score=${this.score}&total=${this.questions.length}&time=${timeTaken}`;
        }, 500);
    }
}

// Initialize quiz when page loads
window.addEventListener('DOMContentLoaded', function() {
    try {
        console.log("DOM fully loaded");
        const quizDataElement = document.getElementById('quiz-data');
        if (!quizDataElement) throw new Error('Quiz data element not found');
        
        const quizData = JSON.parse(quizDataElement.textContent);
        console.log("Quiz data loaded:", quizData);
        
        if (!quizData.quizId || !quizData.questions || !quizData.timeLimit) {
            throw new Error('Invalid quiz data structure');
        }
        
        // Initialize quiz
        window.currentQuiz = new Quiz(quizData.quizId, quizData.questions, quizData.timeLimit);
    } catch (error) {
        console.error('Quiz initialization error:', error);
        alert('Error loading quiz. Please try again.');
    }
});