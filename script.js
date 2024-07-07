document.addEventListener("DOMContentLoaded", function() {
	document.querySelector("form").addEventListener("submit", function(event) {
		event.preventDefault();
		var terms = document.querySelector("#terms").value;
		var xhr = new XMLHttpRequest();
		xhr.open("POST", "classify.php", true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange = function() {
			if (xhr.readyState === 4 && xhr.status === 200) {
				document.querySelector("#result").innerHTML = xhr.responseText;
			}
		};
		xhr.send("terms=" + encodeURIComponent(terms));
	});
});
document.getElementById('reset-btn').addEventListener('click', function() {
    document.getElementById('classify-form').reset();
    document.getElementById('result').innerHTML = '';
});