<?php

namespace App\Parsers;

use App\Models\Answer;
use App\Models\Question;
use DiDom\Document;
use Exception;

class AlleParser extends Parser
{
    private function logQuestion(string $question, string $answer, int $answerLength, string $page, int $questionNumber): void
    {
        $message = "Saved question $questionNumber at $page page";
        $context = ['Question' => $question, 'Answer' => $answer, 'Answer length' => $answerLength];

        if ($_ENV['LOGGER_MODE'] === 'debug') {
            $this->log('Parser', STDOUT, 100, $message, $context);
        } else if ($_ENV['LOGGER_MODE'] === 'file') {
            $this->log('Parser', STORAGE_PATH, 200, $message, $context);
        }
    }

    private function saveQuestionsAndAnswersToDB(array $questionsAndAnswers, string $page): void
    {
        $questions = $questionsAndAnswers['questions'];
        $answers = $questionsAndAnswers['answers'];
        $answersLengths = $questionsAndAnswers['length'];

        for ($i = 0; $i < count($questions); $i++) {
            $question = Question::firstOrCreate(['text' => $questions[$i]['title']]);
            $answer = Answer::firstOrCreate(['text' => $answers[$i]['title']]);

            if (!$question->answers()->where('answer_id', $answer->id)->exists()) {
                $question->answers()->attach($answer->id);
            }

            if (!$answer->questions()->where('question_id', $question->id)->exists()) {
                $answer->questions()->attach($question->id);
            }

            $this->logQuestion($questions[$i]['title'], $answers[$i]['title'], $answersLengths[$i], $page, $i + 1);
        }
    }

    private function getQuestionsAndAnswers(array $secondDnrgLists): array
    {
        $questionsAndAnswers = [
            'questions' => [],
            'answers' => [],
            'length' => [],
        ];

        foreach ($secondDnrgLists as $secondDnrgList) {
            $this->loadDocument($this->url . '/' . $secondDnrgList['href']);

            $questionsListNode = $this->document->find('.Question a');
            $answersListNode = $this->document->find('.AnswerShort a');

            $questionsList = $this->getListInfo($questionsListNode);
            $answersList = $this->getListInfo($answersListNode);
            $answersLength = $this->getAnswersLength($answersList);

            array_push($questionsAndAnswers['questions'], ...$questionsList);
            array_push($questionsAndAnswers['answers'], ...$answersList);
            array_push($questionsAndAnswers['length'], ...$answersLength);

            $this->saveQuestionsAndAnswersToDB($questionsAndAnswers, $secondDnrgList['href']);
        }

        return $questionsAndAnswers;
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
            $this->loadDocument($this->url . '/uebersicht.html');

            $dnrgList = $this->getDnrgList($this->document);
            $secondDnrgLists = $this->getSecondDnrgLists($dnrgList);
            $questionsAndAnswers = $this->getQuestionsAndAnswers($secondDnrgLists);
            print_r($questionsAndAnswers);
        } catch (Exception $err) {
            echo $err->getMessage();
        }

        echo 'Parsing ended';
    }
}
