<?php

return [
    'free_quota' => [
        'cover_letter_generation' => 1,
        'career_report_generation' => 1,
        'chatbot_message' => 20,
        'mock_interview_session' => 1,
    ],

    'subscription_features' => [
        'cover_letter_generation',
        'career_report_generation',
        'chatbot_message',
        'mock_interview_session',
    ],

    'candidate_features' => [
        'cover_letter_generation',
        'career_report_generation',
        'chatbot_message',
        'mock_interview_session',
    ],

    'employer_features' => [
        'employer_featured_job_7d',
        'employer_featured_job_30d',
        'employer_shortlist_ai_explanation',
        'employer_candidate_compare_ai',
        'interview_copilot_generate',
        'interview_copilot_evaluate',
    ],

    'feature_labels' => [
        'cover_letter_generation' => 'Sinh thư xin việc AI',
        'career_report_generation' => 'Sinh báo cáo định hướng nghề nghiệp',
        'chatbot_message' => 'Chatbot tư vấn nghề nghiệp',
        'mock_interview_session' => 'Mock Interview',
        'employer_featured_job_7d' => 'Featured Job 7 ngày',
        'employer_featured_job_30d' => 'Featured Job 30 ngày',
        'employer_shortlist_ai_explanation' => 'AI Shortlist ứng viên',
        'employer_candidate_compare_ai' => 'AI Compare ứng viên',
        'interview_copilot_generate' => 'AI Interview Copilot',
        'interview_copilot_evaluate' => 'AI Evaluate Interview',
    ],

    'featured_job_options' => [
        'employer_featured_job_7d' => [
            'days' => 7,
            'label' => 'Featured Job 7 ngày',
            'badge' => 'Nổi bật 7 ngày',
        ],
        'employer_featured_job_30d' => [
            'days' => 30,
            'label' => 'Featured Job 30 ngày',
            'badge' => 'Nổi bật 30 ngày',
        ],
    ],
];
