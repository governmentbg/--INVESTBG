-- ORIGINAL REPO: https://github.com/quasoft/postgres-tsearch-bulgarian

-- Copy the three files bulgarian.affix, bulgarian.dict and bulgarian.stop to your $SHAREDIR/tsearch_data/ directory
-- You can determine what your $SHAREDIR is by running pg_config --sharedir.

CREATE TEXT SEARCH CONFIGURATION bulgarian (COPY = simple);
CREATE TEXT SEARCH DICTIONARY bulgarian_ispell (
    TEMPLATE = ispell,
    DictFile = bulgarian, 
    AffFile = bulgarian, 
    StopWords = bulgarian
);
CREATE TEXT SEARCH DICTIONARY bulgarian_simple (
    TEMPLATE = pg_catalog.simple,
    STOPWORDS = bulgarian
);
ALTER TEXT SEARCH CONFIGURATION bulgarian ALTER MAPPING FOR asciiword, asciihword, hword, hword_part, word WITH bulgarian_ispell, bulgarian_simple;

-- TO VERIFY
-- SELECT to_tsvector('bulgarian', 'текстовете');
-- SHOULD PRODUCE: "'текст':1"

-- BASIC SEARCHES IN OTHER CHARSETS WORK TOO
-- SELECT to_tsvector('bulgarian', 'Елеико Öppen Bar е щангата на патриотите') @@ websearch_to_tsquery('щанга');
-- SELECT to_tsvector('bulgarian', 'Елеико Öppen Bar е щангата на патриотите') @@ websearch_to_tsquery('bar');
-- SELECT to_tsvector('bulgarian', 'Елеико Öppen Bar е щангата на патриотите') @@ websearch_to_tsquery('Öppen');
