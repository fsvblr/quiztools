import { createApp } from 'vue'
import Quiz from './components/Quiz.vue'

const QuizApp = createApp(Quiz)
QuizApp.provide('Joomla', Joomla)
QuizApp.mount('#quiz-wrap')
