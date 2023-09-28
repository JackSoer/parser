<?php

namespace App\Parsers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Task;
use DiDom\Document;
use Exception;

class AlleParser extends Parser
{
    private function logQuestion(string $question, string $answer, int $answerLength, string $page): void
    {
        $message = "Saved question $question at $page page";
        $context = ['Question' => $question, 'Answer' => $answer, 'Answer length' => $answerLength];

        if ($_ENV['LOGGER_MODE'] === 'debug') {
            $this->log('Parser', STDOUT, 100, $message, $context);
        } else if ($_ENV['LOGGER_MODE'] === 'file') {
            $this->log('Parser', STORAGE_PATH, 200, $message, $context);
        }
    }

    private function logError(string $error): void
    {
        $message = "Error: $error";

        if ($_ENV['LOGGER_MODE'] === 'debug') {
            $this->log('Parser', STDOUT, 100, $message);
        } else if ($_ENV['LOGGER_MODE'] === 'file') {
            $this->log('Parser', STORAGE_PATH, 200, $message);
        }
    }

    private function saveQuestionsAndAnswersToDB(array $questionsAndAnswers, string $page): void
    {
        $questions = $questionsAndAnswers['questions'];
        $answers = $questionsAndAnswers['answers'];
        $answersLengths = $questionsAndAnswers['length'];

        for ($i = 0; $i < count($questions); $i++) {
            $question = Question::firstOrCreate(['text' => $questions[$i]['title']]);
            $isNewQuestion = $question->wasRecentlyCreated;

            $answer = Answer::firstOrCreate(['text' => $answers[$i]['title']]);

            if (!$question->answers()->where('answer_id', $answer->id)->exists()) {
                $question->answers()->attach($answer->id);
            }

            if (!$answer->questions()->where('question_id', $question->id)->exists()) {
                $answer->questions()->attach($question->id);
            }

            if ($isNewQuestion) {
                $this->logQuestion($questions[$i]['title'], $answers[$i]['title'], $answersLengths[$i], $page);
            }
        }
    }

    private function saveQuestionsAndAnswers(mixed $tasks): void
    {
        $questionsAndAnswers = [
            'questions' => [],
            'answers' => [],
            'length' => [],
        ];

        foreach ($tasks as $task) {
            try {
                $this->loadDocument($this->url . '/' . $task['url']);

                $questionsListNode = $this->document->find('.Question a');
                $answersListNode = $this->document->find('.AnswerShort a');

                $questionsList = $this->getListInfo($questionsListNode);
                $answersList = $this->getListInfo($answersListNode);
                $answersLength = $this->getAnswersLength($answersList);

                array_push($questionsAndAnswers['questions'], ...$questionsList);
                array_push($questionsAndAnswers['answers'], ...$answersList);
                array_push($questionsAndAnswers['length'], ...$answersLength);

                $this->saveQuestionsAndAnswersToDB($questionsAndAnswers, $task['url']);

                $task->updateStatus('completed');
            } catch (Exception $err) {
                $task->updateStatus('error');
                $this->logError($err->getMessage());
            }
        }
    }

    private function getAnswersLength(array $answersList): array
    {
        $answersLength = [];

        foreach ($answersList as $answer) {
            array_push($answersLength, strlen($answer['title']));
        }

        return $answersLength;
    }

    private function getDnrgList(Document $document): array
    {
        $dnrgItemsNodes = $document->find('.dnrg li a');

        // Delete Sonstige Link
        unset($dnrgItemsNodes[count($dnrgItemsNodes) - 1]);

        $dnrgList = $this->getListInfo($dnrgItemsNodes);

        return $dnrgList;
    }

    private function getSecondDnrgLists(array $firstDnrgList): array
    {
        $secondDnrgLists = [];

        foreach ($firstDnrgList as $firstDnrgListItem) {
            $this->loadDocument($this->url . '/' . $firstDnrgListItem['href']);
            $secondDnrgList = $this->getDnrgList($this->document);
            $secondDnrgLists = [...$secondDnrgLists, ...$secondDnrgList];
        }

        return $secondDnrgLists;
    }

    public function run(): void
    {
        echo 'Parsing was started...' . PHP_EOL;

        try {
            if (Task::count() === 0) {
                echo 'Creating tasks';

                $this->loadDocument($this->url . '/uebersicht.html');

                $dnrgList = $this->getDnrgList($this->document);
                $secondDnrgLists = $this->getSecondDnrgLists($dnrgList);

                $this->initTasks($secondDnrgLists);
            }

            if (!Task::where('status', 'pending')->exists()) {
                Task::where('status', 'completed')->update(['status' => 'pending']);
                file_put_contents(STORAGE_PATH, '');
            }

            $tasks = Task::where('status', 'pending')->get();

            $this->saveQuestionsAndAnswers($tasks);
        } catch (Exception $err) {
            echo $err->getMessage();
        }

        echo PHP_EOL . 'Parsing ended';
    }

    public function initTasks(array $list): void
    {
        foreach ($list as $item) {
            Task::create([
                'url' => $item['href'],
                'status' => 'pending',
            ]);

            echo "Task " . $item['href'] . " was created" . PHP_EOL;
        }
    }
}
