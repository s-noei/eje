<?php

namespace App\Game\Support;

/**
 * Game rule tables from the legacy include/constants.php.
 */
final class Constants
{
    public const MILI_RANKS = ['Soldier', 'Dathapatish', 'Dathapatish', 'Dathapatish', 'Satapatish', 'Satapatish',
        'Satapatish', 'Hazarapatish', 'Hazarapatish', 'Baivarapatish', 'Baivarapatish'];

    public const RANK_DAMAGES = [0, 200, 500, 1000, 2500, 7500, 15000, 35000, 80000, 200000, 3000000, 20000000];

    public const PUB_RANKS = ['Child', 'Social Level 1', 'Martial Level 1', 'Political Level 1', 'Economical Level 1', 'Social Level 2',
        'Political Level 2', 'Martial Level 2', 'Social Level 3', 'Social Level 4', 'Social Level 5', 'Social Level 6',
        'Social Level 7', 'Social Level 8', 'Social Level 9', 'Social Level 10', 'Social Level 11', 'Social Level 12'];

    public const PUB_EPS = [0, 15, 35, 60, 100, 150, 250, 400, 600, 1000, 1500, 2500, 4000, 6500, 10000, 15000, 22000, 30000];

    public const PUB_REWARDS = [0, 0, 0, 0, 0, 5, 0, 0, 5, 5, 5, 10, 10, 10, 15, 15, 15, 20];

    public const PUB_COLORS = ['rgb(255, 255, 255)', 'rgb(0, 255, 0)', 'rgb(255, 0, 0)', 'rgb(255, 168, 0)',
        'rgb(0, 0, 255)', 'rgb(0, 225, 0)', 'rgb(168, 168, 0)', 'rgb(0, 200, 0)',
        'rgb(0, 175, 0)', 'rgb(0, 150, 0)', 'rgb(0, 125, 0)', 'rgb(0, 100, 0)',
        'rgb(0, 75, 0)', 'rgb(0, 65, 0)', 'rgb(0, 60, 0)', 'rgb(0, 50, 0)',
        'rgb(0, 40, 0)', 'rgb(0, 30, 0)'];

    public const SP_CPS = [0, 0, 110, 350, 850, 1900, 3400, 5400, 8000, 10500,
        13500, 16500, 19650, 22950, 26400, 30000, 33800,
        37800, 42000, 46500, 51350, 56500, 62000, 68000,
        74500, 81500, 89000, 97000, 105000, 115000, 130000,
        150000, 174000, 200000];

    public const GUEST_LEVEL = 0;
    public const USER_LEVEL = 1;
    public const FORUMMOD_LEVEL = 2;
    public const POLICE_LEVEL = 6;
    public const SUPERMOD_LEVEL = 7;
    public const GUARD_LEVEL = 8;
    public const ADMIN_LEVEL = 9;

    public const ELECTIONS_PP_DAY = 18;
    public const ELECTIONS_CG_DAY = 28;
    public const ELECTIONS_CP_DAY = 15;
    public const CG_PROPOSE_START = 19;
    public const CG_PROPOSE_DUE = 26;
    public const CG_SORT_DAY = 27;

    public const WALL_HEIGHT_BASE = 10000;
    public const POP_WALL_STANDARD = 120;

    public const TICKET_PRIORITIES = [];
}
