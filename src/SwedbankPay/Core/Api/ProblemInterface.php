<?php

namespace SwedbankPay\Core\Api;

interface ProblemInterface
{
    const TYPE = 'type';
    const TITLE = 'title';
    const STATUS = 'status';
    const DETAIL = 'detail';
    const PROBLEMS = 'problems';

    const PROBLEM_NAME = 'name';
    const PROBLEM_DESCRIPTION = 'description';

    /**
     * Get type.
     *
     * @return string
     */
    public function getType();

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus();

    /**
     * Get detail.
     *
     * @return string
     */
    public function getDetail();

    /**
     * Get problems.
     *
     * @return array
     */
    public function getProblems();
}
