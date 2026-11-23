-- Bandeja de salida de correos (outbox): las peticiones solo insertan el mensaje y
-- el proceso bin/outbox-worker.php lo envía con reintentos. Si el SMTP falla, la
-- petición no se rompe y el correo sale cuando el servidor vuelve. Los enviados se
-- borran, porque llevan enlaces con tokens.
CREATE TABLE a_outbox (
  o_key BIGINT NOT NULL AUTO_INCREMENT,
  o_recipient VARCHAR(254) NOT NULL,
  o_subject VARCHAR(200) NOT NULL,
  o_body MEDIUMTEXT NOT NULL,
  o_attempts INT NOT NULL DEFAULT 0,
  -- UTC; el siguiente intento no se hace antes de esta fecha (espera exponencial).
  o_next_attempt_at DATETIME NOT NULL,
  -- UTC; se rellena cuando se agotan los reintentos y el mensaje se da por perdido.
  o_failed_at DATETIME NULL,
  o_last_error VARCHAR(200) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (o_key),
  KEY ix_outbox_pending (o_failed_at, o_next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
