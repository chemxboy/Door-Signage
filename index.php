<?php include('inc/header.php'); ?>

<?php
// Here we call the twitter class and get our last tweet.
include 'inc/QuickTwitter.class.php';
$twitter = new QuickTwitter();
$tweets = $twitter->fetchTweets('yorutwitterid');
?>

<?php 
//Here we process the returned tweet and set our header and body variables.
if (is_array($tweets)) {
foreach($tweets as $tweet) {
	$tweet_date = date('M jS  Y @ h:i a');
	$tweet_content = $tweet['text'];
	$tweet_exploded = explode( ':', $tweet_content );
	if ($tweet_exploded[1] == null) {
	$tweet_header = 'IT Message';
	$tweet_body = $tweet_exploded[0];		
	} else {
	$tweet_header = $tweet_exploded[0];
	$tweet_body = $tweet_exploded[1];
	}
} 
} else {
	$tweet_header = 'IT Message';
	$tweet_body = $tweets;
}
?>

<div id="main">	
		<div id="status">
		<table id="status-table">
		<thead>  
            <tr> 
            	<th scope="col" id="col1">
            	<? echo $tweet_header; ?>
            	</th>
            </tr>  
        </thead> 
        <tbody>
        	<tr>
        		<td>
				<? echo $tweet_body; ?>
				</td>
			</tr>
		 </tbody>
		  <tfoot>  
            <tr>  
                <td><? echo 'posted on '.$tweet_date; ?></td> 
            </tr>  
        </tfoot> 
	</table>  
	</div> <!-- end of #status -->
</div> <!-- end of #main -->

<?php include('inc/footer.php'); ?>
