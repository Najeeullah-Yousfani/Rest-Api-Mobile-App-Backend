
<html>
<p align="center">
<img src="https://wpostmedia.s3.eu-west-2.amazonaws.com/utility/svg_swike_logo.svg" alt="Build Status">
</p>
<h1><b> Swike App </b></h1>
<p>Backend Api's</p>
<h3>Before moving this to any new server pleae make sure the following points</h3></br>
    <p>Run all the migrations and seeders</p></br>
    <p>At the end please make sure that each of the default_topics,countries table has "all" named row with id=0 </p></br>

<h2> Developer's Key Points </h2></br>
  <p>For validation swike is using custom request classes that can be found under Requests folder app/Http/Requests</p></br>
  <p>All algorithms are on sql database functions area</p></br>
  <p>These algorithms can be shifted to any other environment just using migration command</p></br>
    <ul>
    <li> Score_Weightage </li>
    <li> get_age_new </li>
    <li> get_age_old </li>
    <li> low_score </li>
    <li> post_age_score_updater </li>
    <li> post_follower_score_updater </li>
    <li> post_repeat_score_updater </li>
    <li> post_score_updater </li>
    <li> update_post_score </li>
    <li> within_ten </li>
  </ul>
  </br> 
  <p>To change any algorithm one must change its my migration rather than changing directly on the sql</p></br>
  <p>missing function in routes file is use to identify missing ids from the routes for example any route using route model binding it will  show the response status 404 on invalid id to change the error from 404 to 400 as invalid id we use missing method </p></br> 
  <p>There are mainly three branches of this repositary: </p></br> 
  <ul>
    <li> development        =>  For development purpose </li>
    <li> quality-assurance  =>  For quality assurance team to test </li>
    <li> staging            =>  For live app </li>
  </ul></br> 
<h2> ERD </h2></br>
<img src="ERD Swike.png">
<h2>  Postman Collection  </h2>
<p> Please find the attached json documentation </p>
<a href="FindUr-App.postman_collection.json" download> Click here for Json file</a></br>
<a href="https://documenter.getpostman.com/view/16849528/UVeKq5VG#4164cdd4-2172-4f3e-9d6d-d4f1976c3f74"> Click here for Web Url</a>
<h3>Note :</h3></br>
<p> All the details regarding every functions and classes are commented above function definations according to user stories</p>
<p> Dont change algorithms directly on sql </p>
</html>
