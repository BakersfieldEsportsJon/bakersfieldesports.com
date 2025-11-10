<?php
/**
 * STEM Education Page
 * Bakersfield eSports Center
 */

$base_path = '../';
$active_page = 'stem';
$page_title = 'STEM Education Through Gaming | Bakersfield eSports Center';
$page_description = 'Discover how we\'re transforming education through innovative game-based learning experiences. STEM programs that combine gaming with rigorous educational content.';
$canonical_url = 'https://bakersfieldesports.com/stem/';

require_once '../includes/schemas.php';
$schema_markup = getOrganizationSchema();

require_once '../includes/head.php';
require_once '../includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero" style="background-image: url('../images/stem-hero.jpg');">
        <div class="container">
            <h1>STEM Education Through Gaming</h1>
            <p>Discover how we're transforming education through innovative game-based learning experiences.</p>
        </div>
    </section>

    <!-- Introduction Section -->
    <section class="intro">
        <div class="container">
            <h2>Bridging Gaming and Education</h2>
            <p>At Bakersfield eSports Center, we believe in the power of games to transform education. Our STEM programs combine the engagement of gaming with rigorous educational content, creating immersive learning experiences that prepare students for the digital future.</p>

            <div class="program-stats">
                <div class="stat">
                    <h3>1000+</h3>
                    <p>Students Impacted</p>
                </div>
                <div class="stat">
                    <h3>15+</h3>
                    <p>School Partners</p>
                </div>
                <div class="stat">
                    <h3>95%</h3>
                    <p>Student Engagement</p>
                </div>
                <div class="stat">
                    <h3>30+</h3>
                    <p>STEM Programs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Programs Section -->
    <section class="core-programs">
        <div class="container">
            <h2>Our Core STEM Programs</h2>

            <!-- Game-Based Learning -->
            <article class="program">
                <div class="program-image">
                    <img alt="Game-Based Learning at Bakersfield eSports" src="../images/game-based-learning.jpg" loading="lazy" width="400" height="300">
                </div>
                <div class="program-content">
                    <h3>Game-Based Learning</h3>
                    <p>Our flagship program uses popular games as educational tools, teaching critical concepts through engaging gameplay experiences. Students learn while playing carefully selected games that reinforce STEM concepts.</p>

                    <h4>Learning Outcomes:</h4>
                    <ul>
                        <li>Critical thinking and problem-solving skills</li>
                        <li>Strategic planning and resource management</li>
                        <li>Data analysis and pattern recognition</li>
                        <li>Team collaboration and communication</li>
                        <li>Adaptability and resilience</li>
                    </ul>

                    <h4>Featured Games:</h4>
                    <ul>
                        <li>Minecraft Education Edition - For engineering and architecture</li>
                        <li>Kerbal Space Program - For physics and astronomy</li>
                        <li>Portal 2 - For physics and spatial reasoning</li>
                        <li>Civilization VI - For history and resource management</li>
                    </ul>
                </div>
            </article>

            <!-- STEM Engineering & Design -->
            <article class="program">
                <div class="program-image">
                    <img alt="STEM Engineering and Design Activities" src="../images/stem-engineering-design.jpg" loading="lazy" width="400" height="300">
                </div>
                <div class="program-content">
                    <h3>Engineering & Design Lab</h3>
                    <p>Our engineering program combines digital tools with hands-on projects, allowing students to design, prototype, and test their creations in both virtual and physical spaces.</p>

                    <h4>Program Features:</h4>
                    <ul>
                        <li>3D modeling and printing workshops</li>
                        <li>Robot building and programming</li>
                        <li>Circuit design and electronics</li>
                        <li>Virtual reality engineering simulations</li>
                        <li>Computer-aided design (CAD) training</li>
                    </ul>

                    <h4>Learning Approach:</h4>
                    <ul>
                        <li>Project-based learning methodology</li>
                        <li>Industry-standard tools and software</li>
                        <li>Real-world problem-solving scenarios</li>
                        <li>Collaborative team projects</li>
                        <li>Professional mentorship opportunities</li>
                    </ul>
                </div>
            </article>

            <!-- Game Development -->
            <article class="program">
                <div class="program-image">
                    <img alt="Video Game Development Workshop" src="../images/video-game-development.jpg" loading="lazy" width="400" height="300">
                </div>
                <div class="program-content">
                    <h3>Game Development Academy</h3>
                    <p>Students learn to create their own games while developing valuable programming, design, and project management skills. Our comprehensive curriculum covers all aspects of game development.</p>

                    <h4>Course Modules:</h4>
                    <ul>
                        <li>Programming Fundamentals (Python, C#)</li>
                        <li>Game Design Principles</li>
                        <li>Unity Engine Development</li>
                        <li>2D and 3D Asset Creation</li>
                        <li>Sound Design and Music</li>
                        <li>User Interface Design</li>
                        <li>Game Testing and Debugging</li>
                    </ul>

                    <h4>Student Achievements:</h4>
                    <ul>
                        <li>Portfolio of completed games</li>
                        <li>Published projects on gaming platforms</li>
                        <li>Participation in game jams</li>
                        <li>Industry mentor connections</li>
                    </ul>
                </div>
            </article>
        </div>
    </section>

    <!-- Success Stories Section -->
    <section class="success-stories">
        <div class="container">
            <h2>Student Success Stories</h2>

            <div class="testimonials">
                <blockquote>
                    "The game development program helped me discover my passion for programming. I'm now pursuing computer science in college with a clear career path in mind."
                    <cite>- Maria R., Program Graduate</cite>
                </blockquote>

                <blockquote>
                    "My son struggled with traditional math classes, but through game-based learning, he's now excelling in geometry and problem-solving. The program made learning fun and relevant."
                    <cite>- John D., Parent</cite>
                </blockquote>

                <blockquote>
                    "The engineering program gave me hands-on experience with real-world tools. I've already used these skills to start my own 3D printing business."
                    <cite>- Alex M., Student Entrepreneur</cite>
                </blockquote>
            </div>
        </div>
    </section>

</main>

<?php require_once '../includes/footer-content.php'; ?>
