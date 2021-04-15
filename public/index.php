<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use function Symfony\Component\String\s;
use Slim\Middleware\MethodOverrideMiddleware;


require __DIR__ . '/../vendor/autoload.php';

$faker = \Faker\Factory::create();
$faker->seed(1234);

$companies = App\GeneratorCompanies::generate(100);

$repo = new App\Users;

$domains = [];
for ($i = 0; $i < 10; $i++) {
    $domains[] = $faker->domainName;
}

$phones = [];
for ($i = 0; $i < 10; $i++) {
    $phones[] = $faker->phoneNumber;
}


session_start();

//$test = new App\Validator();
//print_r($test->validate([1, 2, 3]));


$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
//$app = AppFactory::createFromContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);

//$app->addErrorMiddleware(true, true, true);

//---ex---
// $app->get('/users', function ($request, $response) {
//     return $response->write('GET /users');
// });

// $app->post('/users', function ($request, $response) {
//     return $response->withStatus(302);
// });


$router = $app->getRouteCollector()->getRouteParser();


$app->get('/courses/{id:[0-9]+}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users', function ($request, $response, $args) use ($repo) {
    $users = $repo->getUsers();
    $term = $request->getQueryParam('term');
    $filtered = collect($users)->filter(function ($user, $key) use ($term) {
        return empty($term) ? true : s($user['name'])->ignoreCase()->startsWith($term);
    })->all();
    $messages = $this->get('flash')->getMessages();
    $params = [
        'term' => $term,
        'users' => $filtered,
        'flash' => $messages
        ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/{id:[0-9]+}', function ($request, $response, $args) use ($repo) {
    $id = $args['id'];
    $user = $repo->find($id);
    if (!$user) {
        return $response->withStatus(404);
    }
    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('usersId');



$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->post('/users', function ($request, $response) use ($repo) {
    $validator = new App\ValidatorUser();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);


    if (count($errors) != 0) {
        $params = [
            'user' => $user,
            'errors' => $errors
        ];
        return $this->get('renderer')->render($response, "users/new.phtml", $params);
    }

    $repo->save($user);
    $this->get('flash')->addMessage('success', 'User Added');
    return $response->withHeader('Location', '/users')->withStatus(302);
});


$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $user = $repo->find($id);
    //print_r($user); 
    $params = [
        'user' => $user,
        'errors' => []
    ];
       //return $response;
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');


$app->patch('/users/{id}', function ($request, $response, array $args) use ($router, $repo)  {
    $id = $args['id'];
    $user = $repo->find($id);
    $data = $request->getParsedBodyParam('user');

    $validator = new App\ValidatorUser();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        $user['name'] = $data['name'];
        $user['email'] = $data['email'];
        $user['password'] = $data['password'];
        $user['passwordConfirmation'] = $data['passwordConfirmation'];
        $user['city'] = $data['city'];
    
        $this->get('flash')->addMessage('success', 'User has been updated');
        
        //$repo->save($user);
        $repo->patch($user);
        
        $url = $router->urlFor('usersId', ['id' => $user['id']]);
        //$url = $router->urlFor('editUser', ['id' => $user['id']]);
        return $response->withRedirect($url);
    }
    
    $data['id'] = $id;
    $params = [
        'user' => $data,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->get('/users/{id}/delete', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $user = $repo->find($id);
    $params = [
        'user' => $user,
    ];
    return $this->get('renderer')->render($response, 'users/delete.phtml', $params);
})->setName('deleteUser');

$app->delete('/users/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    $repo->destroy($id);
    $this->get('flash')->addMessage('success', 'School has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});







//-----------------------------------------------

$app->get('/courses', function ($request, $response, $args) {
    $params = [];
    // $params = [
    //     'title' => $title,
    //     'paid' => $paid
    //     ];
    return $this->get('renderer')->render($response, 'courses/index.phtml', $params);
})->setName('courses');

$app->get('/courses/new', function ($request, $response) {
    $params = [
        'course' => ['title' => '', 'paid' => ''],
        'errors' => []
        ];
    return $this->get('renderer')->render($response, "courses/new.phtml", $params);
    //return $this->get('renderer')->render($response, "courses/new.phtml");
});

$app->post('/courses', function ($request, $response) use ($router){
    $validator = new App\ValidatorCourse;
    $course = $request->getParsedBodyParam('course');
    $errors = $validator->validate($course);

    //var_dump($errors);

    if (count($errors) === 0) {
        //print_r('YAP!');
        //print_r($course);
        return $response->withRedirect($router->urlFor('courses'));
    }

    $params = [
        'course' => $course,
        'errors' => $errors
    ];

    //print_r($course);
    return $this->get('renderer')->render($response, "courses/new.phtml", $params);
    //return $response->withHeader('Location', '/courses')->withStatus(302);
});

//------------------------------------------









$app->get('/phones', function (Request $request, Response $response, array $args) use ($phones) {
    $response->getBody()->write(json_encode($phones));
    return $response;
});

$app->get('/domains', function (Request $request, Response $response, array $args) use ($domains) {
    $response->getBody()->write(json_encode($domains));
    return $response;
});

$app->get('/companies', function (Request $request, Response $response, array $args) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $response->getBody()->write(json_encode(array_slice($companies, ($page - 1) * $per, $per)));
    return $response;
});

$app->get('/companies/{id}', function (Request $request, Response $response, array $args) use ($companies) {
    $id = $args['id'];
    $company = collect($companies)->firstWhere('id', $id);
    if (!$company) {
        return $response->write("Page not found.")->withStatus(404);
    }
    $response->getBody()->write(json_encode($company));
    return $response;
});

$app->get('/test', function ($request, $response) use ($users) {
    return $response->withStatus(404);
});



$app->get('/foo', function ($req, $res) {
    // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
    // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
    // Например можно ввести тип success и отражать его зелёным цветом (на Хекслете такого много)
    $this->get('flash')->addMessage('success', 'This is a message');

    return $res->withRedirect('/bar');
});

$app->get('/bar', function ($req, $res, $args) {
    // Извлечение flash сообщений установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages();
    //print_r($messages); // => ['success' => ['This is a message']]

    $params = ['flash' => $messages];
    return $this->get('renderer')->render($res, 'bar.phtml', $params);
});


$app->run();
