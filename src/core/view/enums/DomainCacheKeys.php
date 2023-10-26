<?php

namespace Lay\core\view\enums;

enum DomainCacheKeys : string
{
    case List = "domain_list";
    case CURRENT = "domain_current";
    case ID = "domain_ids";
    case CACHED = "domains_cached";
}
