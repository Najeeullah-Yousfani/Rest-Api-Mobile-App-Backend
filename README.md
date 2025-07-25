
<html>
    <h1 align="center"><b>REST API Mobile App Backend (Social Media App)</b></h1>

<p align="center">
A <b>scalable Laravel-based REST API backend</b> designed for a social-media-like mobile application.<br>
Supports <b>user management, content ranking, personalized advertisements, and moderation</b>, optimized for <b>high performance & low API response times</b>.
</p>

<hr>

<h2>🚀 Key Features</h2>

<h3>✅ Performance & Optimization</h3>
<ul>
  <li><b>Algorithm-Based Post Ranking</b> – Stored as <b>SQL functions</b> and deployed via <b>Laravel migrations</b> to significantly reduce API response times.</li>
  <li><b>Normalized Database Design</b> – 30+ interrelated tables, optimized for scalability and complex queries.</li>
</ul>

<h3>✅ User & Content Management</h3>
<ul>
  <li><b>OAuth2-based Authentication</b> with role-based access control.</li>
  <li><b>Posts, Reactions & Notifications</b> with real-time engagement tracking.</li>
  <li><b>Moderation</b> – Suspicious activities & reports detection.</li>
</ul>

<h3>✅ Personalization</h3>
<ul>
  <li><b>Targeted Advertisements</b> – Filtered by gender, location, and topics.</li>
  <li><b>Post Ranking Algorithms</b> – Includes:
    <ul>
      <li>Score_Weightage</li>
      <li>post_age_score_updater</li>
      <li>post_follower_score_updater</li>
      <li>post_repeat_score_updater</li>
      <li>update_post_score</li>
      <li><i>(and more, see Developer Notes)</i></li>
    </ul>
  </li>
</ul>

<hr>

<h2>🛠️ Tech Stack</h2>
<ul>
  <li><b>Backend Framework:</b> Laravel (PHP)</li>
  <li><b>Database:</b> MySQL with SQL functions for algorithm optimization</li>
  <li><b>Authentication:</b> OAuth2</li>
  <li><b>API Documentation:</b> Postman Collection included</li>
</ul>

<hr>

<h2>⚙️ Installation & Setup</h2>
<ol>
  <li><b>Clone the repository</b><br>
    <pre>git clone https://github.com/Najeeullah-Yousfani/Rest-Api-Mobile-App-Backend.git</pre>
  </li>
  <li><b>Install dependencies</b><br>
    <pre>composer install</pre>
  </li>
  <li><b>Run migrations & seeders</b><br>
    <pre>php artisan migrate --seed</pre>
  </li>
  <li><b>Important:</b> Ensure <code>default_topics</code> and <code>countries</code> table have an <b>“all” row</b> with <b>id = 0</b>.</li>
</ol>

<hr>

<h2>🗄 Database Design (ERD)</h2>
<img src="ERD%20Swike.png" alt="ERD">

<hr>

<h2>📖 API Documentation</h2>
<ul>
  <li><b>Postman JSON:</b> <a href="FindUr-App.postman_collection.json" download>Download here</a></li>
  <li><b>Web Documentation:</b> <a href="https://documenter.getpostman.com/view/16849528/UVeKq5VG#4164cdd4-2172-4f3e-9d6d-d4f1976c3f74">View Here</a></li>
</ul>

<hr>

<h2>👨‍💻 Developer Notes</h2>

<h3>Validation</h3>
<ul>
  <li>the app uses <b>custom request classes</b> located in <code>app/Http/Requests</code>.</li>
</ul>

<h3>Algorithms</h3>
<ul>
  <li>All ranking & scoring algorithms are implemented as <b>SQL database functions</b>.</li>
  <li>These algorithms can be shifted to any other environment using <b>Laravel migration commands</b> (no direct SQL modification required).</li>
  <li>Available Algorithms:
    <ul>
      <li>Score_Weightage</li>
      <li>get_age_new</li>
      <li>get_age_old</li>
      <li>low_score</li>
      <li>post_age_score_updater</li>
      <li>post_follower_score_updater</li>
      <li>post_repeat_score_updater</li>
      <li>post_score_updater</li>
      <li>update_post_score</li>
      <li>within_ten</li>
    </ul>
  </li>
  <li><b>Important:</b> To modify any algorithm, change its migration rather than editing SQL directly.</li>
</ul>

<h3>Routing</h3>
<ul>
  <li>The <b>missing function</b> in the routes file identifies missing IDs using route model binding.  
  Example: For invalid IDs, it changes response from <code>404</code> to <code>400</code> (invalid ID).</li>
</ul>

<h3>Branches</h3>
<ul>
  <li><code>development</code> → For development purposes</li>
  <li><code>quality-assurance</code> → For QA team testing</li>
  <li><code>staging</code> → For live app testing</li>
</ul>

<hr>

<h2>✨ Author</h2>
<p><b>Najeeullah Yousfani</b><br>
<a href="https://linkedin.com/in/your-link">LinkedIn</a> | 
<a href="https://github.com/Najeeullah-Yousfani">GitHub</a>
</p>


</html>
