use Kreait\Firebase\Factory;

$serviceAccount = 'C:/xampp/htdocs/project/library-management-syste-d7b1f-firebase-adminsdk-fbsvc-da41c47c9e.json';

$firebase = (new Factory)
    ->withServiceAccount($serviceAccount)
    ->withDatabaseUri('https://library-management-syste-d7b1f-default-rtdb.firebaseio.com'); // <-- Use the correct database URL

$database = $firebase->createDatabase();
