

/* definicja tabeli kategorii */
CREATE TABLE category
	(
		id_category serial primary key,
		id_parent INT,
		lft INT NOT NULL,
		rgt INT NOT NULL
	);


/* definicja tabeli z tlumaczeniami */
create table category_trans
    (
		id_category_trans serial  primary key,
		id_category int  unique constraint category_trans_ibfk_1 references category on delete cascade,
		language_code varchar(5) not null,
		title varchar(100) not null,
		description varchar(255) not null
    );



/* definicja funkcji usuwajacej podrzedne kategorie  */
CREATE FUNCTION trigger_function()
	RETURNS TRIGGER
AS $$
declare
  myLft integer;
  myRgt integer;
  mywidth integer;
BEGIN
  myLft := old.lft;
  myRgt := old.rgt;
  mywidth := old.rgt - old.lft + 1;

  DELETE FROM category
  WHERE lft BETWEEN myLft AND myRgt
  AND category.id_category <> old.id_category;

  UPDATE category
     SET rgt = rgt - myWidth
  WHERE rgt > myRgt;

  UPDATE category
    SET lft = lft - myWidth
  WHERE lft > myRgt;

  return old;
END
$$
LANGUAGE plpgsql;



/* definicja trigera ktory wywoluje funkcje po usunieciu kategorii  */
CREATE TRIGGER category_del
AFTER DELETE ON category
FOR EACH ROW
EXECUTE PROCEDURE trigger_function();