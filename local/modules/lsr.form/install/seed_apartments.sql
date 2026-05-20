-- Заполнение b_lsr_form_apartment 10000 тестовыми записями.
-- Предполагается: в b_lsr_form_house уже есть 100 домов с ID от 1 до 100.
-- Распределение: по 100 квартир на каждый дом (HOUSE_ID = 1..100, NUMBER = 1..100).
-- Статусы (STATUS): ~70% F (свободна), ~20% B (забронирована), ~10% S (продана).
-- Требуется MySQL 8.0+ (recursive CTE).

-- Очистка зависимых данных перед перезаливкой (есть уникальный индекс HOUSE_ID+NUMBER
-- и внешний ключ из b_lsr_form_request -> b_lsr_form_apartment).
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM b_lsr_form_request;
DELETE FROM b_lsr_form_apartment;
ALTER TABLE b_lsr_form_apartment AUTO_INCREMENT = 1;
ALTER TABLE b_lsr_form_request   AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

SET SESSION cte_max_recursion_depth = 10000;

INSERT INTO b_lsr_form_apartment (HOUSE_ID, NUMBER, STATUS)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 10000
)
SELECT
    ((n - 1) DIV 100) + 1                       AS HOUSE_ID,
    CAST(((n - 1) MOD 100) + 1 AS CHAR)         AS NUMBER,
    CASE
        WHEN RAND() < 0.70 THEN 'F'
        WHEN RAND() < 0.875 THEN 'B'
        ELSE 'S'
    END                                         AS STATUS
FROM seq;
