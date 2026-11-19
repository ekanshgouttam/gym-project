<?php

/*
|--------------------------------------------------------------------------
| Gym website (homepage) content
|--------------------------------------------------------------------------
| Default copy and media for the premium gym website at "/". The gym it
| represents is chosen in Super Admin → Platform settings ("Website gym");
| that gym's name, address, phone, hours, trainers and membership plans are
| pulled from the database. Media paths are relative to /public.
|
| The member stories and testimonials below are SAMPLE CONTENT for the demo
| gym. Replace them with real members' words (with their permission) before
| the site goes live.
*/

return [
    'hero' => [
        'eyebrow' => 'Strength · Conditioning · Coaching',
        'headline' => ['BUILD YOUR', 'STRONGEST', 'SELF.'],
        'subtitle' => 'Transform your body. Strengthen your mind. Become unstoppable.',
        'video' => 'media/site/hero.mp4',
        'video_mobile' => 'media/site/hero-mobile.mp4',
        'poster' => 'media/site/hero.webp',
        'poster_mobile' => 'media/site/hero-sm.webp',
    ],

    'about' => [
        'title' => ['NO SHORTCUTS.', 'JUST WORK.'],
        'lead' => 'We built a gym for people who want results they can see and feel. Serious equipment, coaches on the floor all day, and a culture that pushes you a little further every session.',
        'body' => 'From your first squat to your first competition, every plan is written for you, checked by a coach and tracked month by month — including AI body scans that show how your physique is changing.',
        'images' => ['media/site/about-rack.webp', 'media/site/about-floor.webp'],
        'pillars' => [
            ['01', 'Coached, not crowded', 'Small coach-to-member ratios and form checks on every big lift.'],
            ['02', 'Built for strength', 'Calibrated plates, competition racks and a turf lane for sleds.'],
            ['03', 'Progress you can see', 'Monthly AI body scans, weight and measurement tracking in the app.'],
        ],
    ],

    // 3D equipment showcase figures.
    'forge' => [
        'stats' => [['2.5–50', 'kg plates'], ['12', 'power racks'], ['1,800', 'sq ft turf']],
    ],

    'programs' => [
        ['slug' => 'strength', 'name' => 'Strength', 'tag' => 'Barbell fundamentals', 'image' => 'media/site/program-strength.webp', 'duration' => '60 min', 'level' => 'All levels',
            'text' => 'Squat, bench, deadlift and press, coached with structured progressive overload.'],
        ['slug' => 'hypertrophy', 'name' => 'Hypertrophy', 'tag' => 'Build muscle', 'image' => 'media/site/program-hypertrophy.webp', 'duration' => '75 min', 'level' => 'Intermediate',
            'text' => 'Volume-driven splits that build size and shape, with weekly technique reviews.'],
        ['slug' => 'boxing', 'name' => 'Boxing', 'tag' => 'Fight conditioning', 'image' => 'media/site/program-boxing.webp', 'duration' => '50 min', 'level' => 'All levels',
            'text' => 'Bag work, pads and footwork that torch calories and sharpen reflexes.'],
        ['slug' => 'hiit', 'name' => 'HIIT & Core', 'tag' => 'Burn and brace', 'image' => 'media/site/program-core.webp', 'duration' => '40 min', 'level' => 'All levels',
            'text' => 'Short, brutal intervals and core circuits for fat loss and a stronger midline.'],
        ['slug' => 'mobility', 'name' => 'Mobility', 'tag' => 'Move better', 'image' => 'media/site/program-mobility.webp', 'duration' => '45 min', 'level' => 'Beginner friendly',
            'text' => 'Stretching and controlled mobility work to unlock range and prevent injuries.'],
        ['slug' => 'functional', 'name' => 'Functional', 'tag' => 'Real-world fitness', 'image' => 'media/site/program-functional.webp', 'duration' => '55 min', 'level' => 'All levels',
            'text' => 'Carries, sleds, kettlebells and bodyweight skills for strength you can use.'],
    ],

    'personal_training' => [
        'title' => ['ONE COACH.', 'ONE GOAL.', 'YOURS.'],
        'lead' => 'One-to-one coaching built around your body, your schedule and your target.',
        'images' => ['media/site/pt-coaching.webp', 'media/site/pt-focus.webp'],
        'steps' => [
            ['Assessment', 'Movement screen, body measurements and a baseline AI body scan.'],
            ['Your blueprint', 'A training and nutrition plan written for you, delivered in the app.'],
            ['Coached sessions', 'Every rep checked. Weights progressed when you are ready.'],
            ['Monthly review', 'Scan, measure, compare — then adjust the plan.'],
        ],
    ],

    'results' => [
        'image' => 'media/site/results.webp',
        'image_2' => 'media/site/results-2.webp',
        // Sample stories (first names only) — replace with real members, with consent.
        'stories' => [
            ['name' => 'Aditya', 'age' => 31, 'weeks' => 16, 'change' => '−9.4 kg', 'goal' => 'Fat loss', 'quote' => 'I stopped guessing. The monthly scans showed changes I couldn\'t see in the mirror yet.'],
            ['name' => 'Meera', 'age' => 27, 'weeks' => 20, 'change' => '+42 kg deadlift', 'goal' => 'Strength', 'quote' => 'My coach fixed my hinge in week one. Twenty weeks later I pulled twice my bodyweight.'],
            ['name' => 'Rohit', 'age' => 38, 'weeks' => 12, 'change' => '−11 cm waist', 'goal' => 'Recomposition', 'quote' => 'Three sessions a week, a simple diet plan and no excuses. It just works.'],
        ],
    ],

    'gallery' => [
        ['type' => 'image', 'src' => 'media/site/gallery-bags.webp', 'alt' => 'Heavy bags lit by morning light', 'size' => 'wide'],
        ['type' => 'video', 'src' => 'media/site/gallery-gym.mp4', 'poster' => 'media/site/about-floor.webp', 'alt' => 'Training on the gym floor', 'size' => 'tall'],
        ['type' => 'image', 'src' => 'media/site/gallery-curl.webp', 'alt' => 'Athlete doing dumbbell curls', 'size' => 'tall'],
        ['type' => 'image', 'src' => 'media/site/gallery-ring.webp', 'alt' => 'Boxing ring after hours', 'size' => 'wide'],
        ['type' => 'image', 'src' => 'media/site/about-rack.webp', 'alt' => 'Dumbbell and plate racks', 'size' => 'tall'],
        ['type' => 'image', 'src' => 'media/site/gallery-boxer.webp', 'alt' => 'Boxer resting between rounds', 'size' => 'wide'],
    ],

    // Sample testimonials — replace with real reviews before going live.
    'testimonials' => [
        ['name' => 'Karan M.', 'role' => 'Member, 2 years', 'text' => 'Best-equipped gym I have trained in, and the coaches actually coach.'],
        ['name' => 'Sneha R.', 'role' => 'Member, 8 months', 'text' => 'Clean, focused, zero ego. I finally look forward to leg day.'],
        ['name' => 'Vikram P.', 'role' => 'Personal training', 'text' => 'Down two belt notches and up 30 kg on my squat. The app keeps me honest.'],
        ['name' => 'Ananya D.', 'role' => 'Boxing classes', 'text' => 'The boxing sessions are the hardest fun I have ever had.'],
        ['name' => 'Rahul K.', 'role' => 'Member, 1 year', 'text' => 'Joining took two minutes with the QR code. Training here changed my year.'],
        ['name' => 'Priya S.', 'role' => 'Member, 6 months', 'text' => 'Seeing my monthly AI scan side by side is the best motivation.'],
    ],
];
