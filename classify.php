<?php
if (isset($_POST['terms'])) {
    // Training data
    $trainingData = array(
        array("Beras", "Minyak Goreng", "Telur", "Nasi Goreng"),
        array("Daging Ayam", "Bayam", "Wortel", "Brokoli", "Susu", "Sup Ayam"),
        array("Roti Tawar", "Selai Kacang", "Keju", "Sandwich"),
        array("Gula", "Tepung", "Telur", "Kue"),
        array("Ayam", "Kunyit", "Jahe", "Opor"),
        array("Udang", "Bawang Putih", "Ketumbar", "Gulai Udang"),
        array("Tahu", "Tempe", "Kacang", "Sate"),
        array("Daging Kambing", "Bawang Merah", "Ketumbar", "Gulai Kambing"),
        array("Ikan", "Tahu", "Tempe", "Bawang Merah", "Pecel Ikan"),
        array("Daging Ayam", "Garam", "Penyedap", "Daun Jeruk", "Kunyit", "Gula", "Daun Salam", "Tomat", "Bawang Merah", "Bawang Putih", "Lengkuas", "Jeruk", "Nipis", "Mie", "Soun", "Sup Ayam dengan Soun"),
        array("Beras", "Minyak", "Cabai", "Tomat", "Kecap", "Saus", "Bumbu Racik", "Nasi Goreng Pedas"),
        array("Kluwek", "Daun Jeruk", "Daun Salam", "Daging", "Sapi", "Bawang", "Perai", "Jahe", "Kunyit", "Kemiri", "Merica", "Ketumbar", "Rawon"),
        array("Lele", "Kemangi", "Cabai", "Tomat", "Tempe", "Tahu", "Timun", "Lele dengan Sambal"),
        array("Mie Instan", "Telur", "Beras", "Mie Instan dengan Telur"),
        array("Tahu", "Garam", "Gula Merah", "Daun Salam", "Lengkuas", "Bawang Merah", "Ketumbar", "Tahu Manis Pedas"),
        array("Ayam", "Wortel", "Kentang", "Daun Kol", "Tomat", "Sosis", "Buncis", "Merica", "Penyedap", "Tumis Ayam Sayuran"),
        array("Bayam", "Jagung", "Bawang Putih", "Merica", "Garam", "Kaldu Ayam", "Sup Bayam dan Jagung"),
        array("Tempe", "Tomat", "Minyak", "Gula Merah", "Saus Tiram", "Cabai", "Bawang Putih", "Gula Pasir", "Tempe Manis Pedas"),
        array("Bumbu Pecel", "Toge", "Sawi", "Tempe", "Peyek", "Pecel"),
        array("Wortel", "Brokoli", "Jamur", "Kol", "Sosis", "Telur", "Bawang Merah", "Saus Tiram", "Penyedap", "Tumis Sayuran dengan Sosis")
    );

    // Normalize terms and training data
    $terms = array_map('trim', explode(',', $_POST['terms']));
    $terms = array_map('strtolower', $terms);
    foreach ($trainingData as &$data) {
        foreach ($data as &$term) {
            $term = strtolower($term);
        }
    }
    unset($data, $term);

    // Calculate term frequencies and class priorities
    $termFrequencies = array();
    $classPriorities = array();
    foreach ($trainingData as $data) {
        $recipe = $data[count($data) - 1];
        if (!isset($termFrequencies[$recipe])) {
            $termFrequencies[$recipe] = array();
        }
        foreach ($data as $term) {
            if ($term != $recipe) {
                if (!isset($termFrequencies[$recipe][$term])) {
                    $termFrequencies[$recipe][$term] = 0;
                }
                $termFrequencies[$recipe][$term]++;
            }
        }
        if (!isset($classPriorities[$recipe])) {
            $classPriorities[$recipe] = 0;
        }
        $classPriorities[$recipe]++;
    }

    // Normalize term frequencies
    foreach ($termFrequencies as &$termFrequency) {
        $sum = array_sum($termFrequency);
        foreach ($termFrequency as &$value) {
            $value /= $sum;
        }
    }
    unset($termFrequency, $value);

    // Normalize class priorities
    $sum = array_sum($classPriorities);
    foreach ($classPriorities as &$value) {
        $value /= $sum;
    }
    unset($value);

    // Display term frequencies and class priorities
    echo "<h2>Term Frequencies</h2>";
    echo "<pre>";
    $output = print_r($termFrequencies, true);
    $output = str_replace(array("Array", ")", "("), "", $output);
    $output = preg_replace('/\](.*)=>/', ']$1:', $output);
    echo $output;
    echo "</pre>";

    echo "<h2>Class Priorities</h2>";
    echo "<pre>";
    $output = print_r($classPriorities, true);
    $output = str_replace(array("Array", ")", "("), "", $output);
    $output = preg_replace('/\](.*)=>/', ']$1:', $output);
    echo $output;
    echo "</pre>";


    // Calculate posterior probabilities
    $posteriorProbabilities = array();
    foreach ($classPriorities as $recipe => $priority) {
        $posteriorProbability = log($priority);
        foreach ($terms as $term) {
            if (isset($termFrequencies[$recipe][$term])) {
                $posteriorProbability += log($termFrequencies[$recipe][$term]);
            } else {
                $posteriorProbability += log(0.01); // Add a small value to avoid log(0)
            }
        }
        $posteriorProbabilities[$recipe] = $posteriorProbability;
    }

    // Display posterior probabilities
    echo "<h2>Posterior Probabilities</h2>";
    echo "<pre>";
    $output = print_r($posteriorProbabilities, true);
    $output = str_replace(array("Array", ")", "("), "", $output);
    $output = preg_replace('/\](.*)=>/', ']$1:', $output);
    echo $output;
    echo "</pre>";

    // Identify the recipe with the highest posterior probability
    $maxRecipe = null;
    $maxProbability = -INF;
    foreach ($posteriorProbabilities as $recipe => $probability) {
        if ($probability > $maxProbability) {
            $maxProbability = $probability;
            $maxRecipe = $recipe;
        }
    }

    // Evaluate classification result
    $truePositives = 0;
    $falsePositives = 0;
    $falseNegatives = 0;
    $maxSimilarity = 0;
    $actualRecipe = null;

    foreach ($trainingData as $data) {
        $recipeTerms = array_slice($data, 0, count($data) - 1);
        $similarity = count(array_intersect($terms, $recipeTerms)) / count($terms);
        if ($similarity > $maxSimilarity) {
            $maxSimilarity = $similarity;
            $actualRecipe = $data[count($data) - 1];
        }
    }

    // If the predicted recipe matches the actual recipe
    if ($maxRecipe == $actualRecipe) {
        $truePositives++;
    } else {
        $falsePositives++;
    }
    // If there is some similarity but not an exact match
    if ($maxRecipe != $actualRecipe && $maxSimilarity > 0) {
        $falseNegatives++;
    }

    $precision = ($truePositives + $falsePositives) > 0 ? $truePositives / ($truePositives + $falsePositives) : 0;
    $recall = ($truePositives + $falseNegatives) > 0 ? $truePositives / ($truePositives + $falseNegatives) : 0;

    // Display classification result
    echo "<h2>Classification: " . $maxRecipe . "</h2>";
    echo "<p>Precision: " . number_format($precision, 4) . "</p>";
    echo "<p>Recall: " . number_format($recall, 4) . "</p>";

    // Display debug information for evaluation
    echo "<h2>Evaluation Debug Info</h2>";
    echo "<p>True Positives: " . $truePositives . "</p>";
    echo "<p>False Positives: " . $falsePositives . "</p>";
    echo "<p>False Negatives: " . $falseNegatives . "</p>";
    // Display debug information for evaluation (continued)
    echo "<p>Actual Recipe: " . $actualRecipe . "</p>";
    echo "<p>Predicted Recipe: " . $maxRecipe . "</p>";
} else {
    echo "<p>Please enter some terms!</p>";
}
