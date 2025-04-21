// Google Analytics implementation
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-XXXXX-Y', 'auto');
ga('send', 'pageview');

class QuizAnalytics {
    static trackEvent(eventCategory, eventAction, eventLabel = null, eventValue = null, additionalData = {}) {
        const trackingData = {
            event_category: eventCategory,
            event_action: eventAction,
            event_label: eventLabel,
            event_value: eventValue,
            ...additionalData,
            url: window.location.href,
            timestamp: new Date().toISOString()
        };

        console.log('[Analytics] Tracking event:', trackingData);

        fetch('includes/track_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(trackingData)
        })

        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                console.error('Tracking failed:', data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Tracking error:', error);
        });
    }

    static trackPageView(pageTitle) {
        this.trackEvent('page', 'view', pageTitle);
    }

    static trackQuizStart(quizId, quizTitle) {
        if (!quizId) return;
        this.trackEvent('quiz', 'start', quizTitle, quizId);
    }

    static trackQuizComplete(quizId, quizTitle, score, totalQuestions, timeTaken) {
        // Convert to numbers explicitly
        const numericScore = Number(score);
        const numericTotal = Number(totalQuestions);
        const numericTime = Number(timeTaken);
        
        if (isNaN(numericScore) || isNaN(numericTotal) || isNaN(numericTime)) {
            console.error('Invalid numeric values in trackQuizComplete');
            return;
        }

        this.trackEvent('quiz', 'complete', quizTitle, numericScore, {
            quiz_id: quizId,
            total_questions: numericTotal,
            time_taken: numericTime
        });
    }

    static trackQuestionAnswer(quizId, questionId, isCorrect, timeTaken) {
        if (!quizId || !questionId) return;
        this.trackEvent('question', isCorrect ? 'correct' : 'incorrect', 
                       questionId.toString(), quizId, {
            time_taken: Math.max(0, timeTaken)
        });
    }

    static trackTimerExpired(quizId, quizTitle) {
        if (!quizId) return;
        this.trackEvent('quiz', 'timer_expired', quizTitle, quizId);
    }
}

// Track initial page view
document.addEventListener('DOMContentLoaded', () => {
    QuizAnalytics.trackPageView(document.title);
});