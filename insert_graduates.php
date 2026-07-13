<?php
// ⚠️ DELETE THIS FILE IMMEDIATELY AFTER RUNNING
// Visit: http://localhost/CertsVerify/insert_graduates.php

$mysqli = require 'processes/db.php';
$inserted = 0; $skipped = 0;

$students = [
    ['Oliver', 'Mwangi', 'University of Nairobi', 'UON/LAW/2020/229', 'oliver.mwangi@gmail.com', '07123456789', 'OliverMwangi123!'],
    ['Isaac', 'Simiyu', 'Kenyatta University', 'KU/BUS/2023/605', 'isaac.simiyu@gmail.com', '07123456789', 'IsaacSimiyu123!'],
    ['Beatrice', 'Wanjiru', 'University of Nairobi', 'UON/LAW/2019/518', 'beatrice.wanjiru@gmail.com', '07123456789', 'BeatriceWanjiru123!'],
    ['Moses', 'Omondi', 'United States International University Africa', 'USIU/HUM/2023/226', 'moses.omondi@gmail.com', '07123456789', 'MosesOmondi123!'],
    ['Collins', 'Wangari', 'Moi University', 'MU/HUM/2019/349', 'collins.wangari@gmail.com', '07123456789', 'CollinsWangari123!'],
    ['Robert', 'Otieno', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/BUS/2018/390', 'robert.otieno@gmail.com', '07123456789', 'RobertOtieno123!'],
    ['George', 'Wambui', 'Egerton University', 'EGU/CS/2020/748', 'george.wambui@gmail.com', '07123456789', 'GeorgeWambui123!'],
    ['Diana', 'Musyoka', 'Kenyatta University', 'KU/MED/2018/850', 'diana.musyoka@gmail.com', '07123456789', 'DianaMusyoka123!'],
    ['Oliver', 'Kiptoo', 'Egerton University', 'EGU/BUS/2019/047', 'oliver.kiptoo@gmail.com', '07123456789', 'OliverKiptoo123!'],
    ['Quinter', 'Mutua', 'Moi University', 'MU/BUS/2019/390', 'quinter.mutua@gmail.com', '07123456789', 'QuinterMutua123!'],
    ['Robert', 'Chebet', 'Egerton University', 'EGU/SCI/2020/215', 'robert.chebet@gmail.com', '07123456789', 'RobertChebet123!'],
    ['Quinter', 'Achieng', 'Kenyatta University', 'KU/ENG/2023/547', 'quinter.achieng@gmail.com', '07123456789', 'QuinterAchieng123!'],
    ['Upendo', 'Mutua', 'Strathmore University', 'STR/MED/2021/948', 'upendo.mutua@gmail.com', '07123456789', 'UpendoMutua123!'],
    ['Oliver', 'Wairimu', 'United States International University Africa', 'USIU/SCI/2023/864', 'oliver.wairimu@gmail.com', '07123456789', 'OliverWairimu123!'],
    ['Xenia', 'Adhiambo', 'University of Nairobi', 'UON/SCI/2018/411', 'xenia.adhiambo@gmail.com', '07123456789', 'XeniaAdhiambo123!'],
    ['Robert', 'Kipchoge', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/SCI/2023/218', 'robert.kipchoge@gmail.com', '07123456789', 'RobertKipchoge123!'],
    ['Patricia', 'Gitau', 'Maseno University', 'MSN/ENG/2021/272', 'patricia.gitau@gmail.com', '07123456789', 'PatriciaGitau123!'],
    ['Isaac', 'Mutua', 'United States International University Africa', 'USIU/HUM/2020/920', 'isaac.mutua@gmail.com', '07123456789', 'IsaacMutua123!'],
    ['Lilian', 'Kimani', 'Egerton University', 'EGU/EDU/2019/094', 'lilian.kimani@gmail.com', '07123456789', 'LilianKimani123!'],
    ['William', 'Wanjiru', 'Kenyatta University', 'KU/ENG/2023/812', 'william.wanjiru@gmail.com', '07123456789', 'WilliamWanjiru123!'],
    ['Richard', 'Owino', 'Catholic University of East Africa', 'CUEA/HUM/2021/611', 'richard.owino@gmail.com', '07123456789', 'RichardOwino123!'],
    ['Diana', 'Akinyi', 'Moi University', 'MU/BUS/2018/699', 'diana.akinyi@gmail.com', '07123456789', 'DianaAkinyi123!'],
    ['Irene', 'Adhiambo', 'Moi University', 'MU/BUS/2020/301', 'irene.adhiambo@gmail.com', '07123456789', 'IreneAdhiambo123!'],
    ['Beatrice', 'Kamau', 'Daystar University', 'DAY/MED/2023/996', 'beatrice.kamau@gmail.com', '07123456789', 'BeatriceKamau123!'],
    ['Gilbert', 'Adhiambo', 'Strathmore University', 'STR/MED/2018/862', 'gilbert.adhiambo@gmail.com', '07123456789', 'GilbertAdhiambo123!'],
    ['Oliver', 'Akinyi', 'Catholic University of East Africa', 'CUEA/SCI/2019/781', 'oliver.akinyi@gmail.com', '07123456789', 'OliverAkinyi123!'],
    ['Kevin', 'Musyoka', 'United States International University Africa', 'USIU/SCI/2022/501', 'kevin.musyoka@gmail.com', '07123456789', 'KevinMusyoka123!'],
    ['Brian', 'Mwangi', 'Egerton University', 'EGU/CS/2019/247', 'brian.mwangi@gmail.com', '07123456789', 'BrianMwangi123!'],
    ['Kemunto', 'Kipchoge', 'Kenyatta University', 'KU/BUS/2021/779', 'kemunto.kipchoge@gmail.com', '07123456789', 'KemuntoKipchoge123!'],
    ['Isaac', 'Otieno', 'Daystar University', 'DAY/MED/2019/541', 'isaac.otieno@gmail.com', '07123456789', 'IsaacOtieno123!'],
    ['Moses', 'Owino', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/LAW/2023/731', 'moses.owino@gmail.com', '07123456789', 'MosesOwino123!'],
    ['Thomas', 'Kimani', 'Egerton University', 'EGU/EDU/2022/124', 'thomas.kimani@gmail.com', '07123456789', 'ThomasKimani123!'],
    ['Pauline', 'Mutua', 'Kenyatta University', 'KU/LAW/2018/603', 'pauline.mutua@gmail.com', '07123456789', 'PaulineMutua123!'],
    ['Oscar', 'Omondi', 'Kenyatta University', 'KU/CS/2023/235', 'oscar.omondi@gmail.com', '07123456789', 'OscarOmondi123!'],
    ['Eunice', 'Auma', 'University of Nairobi', 'UON/LAW/2018/286', 'eunice.auma@gmail.com', '07123456789', 'EuniceAuma123!'],
    ['Quinter', 'Gitau', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/EDU/2019/249', 'quinter.gitau@gmail.com', '07123456789', 'QuinterGitau123!'],
    ['Edwin', 'Njenga', 'Maseno University', 'MSN/BUS/2018/675', 'edwin.njenga@gmail.com', '07123456789', 'EdwinNjenga123!'],
    ['Beatrice', 'Wambui', 'Maseno University', 'MSN/CS/2021/690', 'beatrice.wambui@gmail.com', '07123456789', 'BeatriceWambui123!'],
    ['Patricia', 'Nyambura', 'Kenyatta University', 'KU/SCI/2021/820', 'patricia.nyambura@gmail.com', '07123456789', 'PatriciaNyambura123!'],
    ['George', 'Mutua', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/EDU/2022/144', 'george.mutua@gmail.com', '07123456789', 'GeorgeMutua123!'],
    ['Pauline', 'Njoroge', 'Kenyatta University', 'KU/BUS/2022/052', 'pauline.njoroge@gmail.com', '07123456789', 'PaulineNjoroge123!'],
    ['Patricia', 'Musyoka', 'University of Nairobi', 'UON/ENG/2019/417', 'patricia.musyoka@gmail.com', '07123456789', 'PatriciaMusyoka123!'],
    ['Florence', 'Gitau', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/ENG/2018/389', 'florence.gitau@gmail.com', '07123456789', 'FlorenceGitau123!'],
    ['Amina', 'Kimani', 'Moi University', 'MU/HUM/2020/714', 'amina.kimani@gmail.com', '07123456789', 'AminaKimani123!'],
    ['Upendo', 'Njenga', 'United States International University Africa', 'USIU/EDU/2023/159', 'upendo.njenga@gmail.com', '07123456789', 'UpendoNjenga123!'],
    ['Michael', 'Kariuki', 'Jomo Kenyatta University of Agriculture and Technology', 'JKUAT/CS/2022/766', 'michael.kariuki@gmail.com', '07123456789', 'MichaelKariuki123!'],
    ['Ursula', 'Wanjiru', 'University of Nairobi', 'UON/ENG/2021/059', 'ursula.wanjiru@gmail.com', '07123456789', 'UrsulaWanjiru123!'],
    ['Gilbert', 'Kipchoge', 'Strathmore University', 'STR/BUS/2022/692', 'gilbert.kipchoge@gmail.com', '07123456789', 'GilbertKipchoge123!'],
    ['Pauline', 'Kimani', 'Kenyatta University', 'KU/CS/2019/635', 'pauline.kimani@gmail.com', '07123456789', 'PaulineKimani123!'],
    ['Faith', 'Owino', 'Catholic University of East Africa', 'CUEA/SCI/2022/958', 'faith.owino@gmail.com', '07123456789', 'FaithOwino123!'],
];

foreach ($students as $s) {
    [$first, $last, $uni, $adm, $email, $phone, $pass] = $s;
    $check = mysqli_query($mysqli, "SELECT id FROM graduate WHERE email='$email' LIMIT 1");
    if (mysqli_num_rows($check) > 0) { $skipped++; continue; }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $sql  = "INSERT INTO graduate (firstname, lastname, universityName, admissionNumber, email, phoneNumber, password, role) VALUES ('$first', '$last', '$uni', '$adm', '$email', '$phone', '$hash', 'Graduate')";
    if (mysqli_query($mysqli, $sql)) $inserted++;
}
echo "<h2 style='font-family:sans-serif;color:green'>Done! Inserted: $inserted | Skipped: $skipped</h2>";
echo "<p style='color:red;font-family:sans-serif'><strong>DELETE this file now!</strong></p>";
?>