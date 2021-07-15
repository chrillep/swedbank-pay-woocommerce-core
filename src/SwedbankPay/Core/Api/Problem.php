<?php

namespace SwedbankPay\Core\Api;

use SwedbankPay\Core\Data;

/**
 * Class Problem
 * @package SwedbankPay\Core\Api
 */
class Problem extends Data implements ProblemInterface
{
    /**
     * Problem constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get detail.
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->getData(self::DETAIL);
    }

    /**
     * Get problems.
     *
     * @return array
     */
    public function getProblems()
    {
        return $this->getData(self::PROBLEMS);
    }

    /**
     * Export problems to string.
     *
     * @return string
     */
    public function toString()
    {
        $problems = $this->getProblems();

        $result = [];
        foreach ($problems as $problem) {
            $result[] = sprintf('(%s) %s', $problem[self::PROBLEM_NAME], $problem[self::PROBLEM_DESCRIPTION]);
        }

        return implode("\n", $result);
    }
}
