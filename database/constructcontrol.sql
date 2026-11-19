--
-- PostgreSQL database dump
--


-- Dumped from database version 15.19 (Homebrew)
-- Dumped by pg_dump version 15.19 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: mglpbc_auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_auditoria (
    id_auditoria bigint NOT NULL,
    id_usuario bigint,
    accion character varying(20) NOT NULL,
    entidad character varying(50) NOT NULL,
    id_registro bigint NOT NULL,
    id_proyecto bigint,
    descripcion character varying(255) NOT NULL,
    cambios jsonb,
    ip character varying(45),
    fecha timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: mglpbc_auditoria_id_auditoria_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_auditoria_id_auditoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_auditoria_id_auditoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_auditoria_id_auditoria_seq OWNED BY public.mglpbc_auditoria.id_auditoria;


--
-- Name: mglpbc_curva_s; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_curva_s (
    id_curva bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    fecha_evaluacion date NOT NULL,
    monto_planificado_acumulado numeric(15,2) NOT NULL,
    monto_real_acumulado numeric(15,2) NOT NULL
);


--
-- Name: mglpbc_curva_s_id_curva_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_curva_s_id_curva_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_curva_s_id_curva_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_curva_s_id_curva_seq OWNED BY public.mglpbc_curva_s.id_curva;


--
-- Name: mglpbc_gastos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_gastos (
    id_gasto bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    id_tipo_gasto bigint NOT NULL,
    id_tipo_costo bigint,
    nombre_gasto character varying(255) NOT NULL,
    descripcion_gasto text,
    monto_gasto numeric(15,2) NOT NULL,
    fecha_gasto date NOT NULL,
    documento_gasto character varying(255),
    cantidad_gasto numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    id_item_presupuesto bigint,
    unidad_gasto character varying(30),
    estado_gasto character varying(255) DEFAULT 'pendiente'::character varying NOT NULL,
    id_usuario_registro bigint,
    id_usuario_revisor bigint,
    fecha_revision timestamp(0) without time zone,
    motivo_rechazo text,
    CONSTRAINT mglpbc_gastos_estado_gasto_check CHECK (((estado_gasto)::text = ANY ((ARRAY['pendiente'::character varying, 'aprobado'::character varying, 'rechazado'::character varying])::text[])))
);


--
-- Name: mglpbc_gastos_id_gasto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_gastos_id_gasto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_gastos_id_gasto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_gastos_id_gasto_seq OWNED BY public.mglpbc_gastos.id_gasto;


--
-- Name: mglpbc_item_presupuesto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_item_presupuesto (
    id_item_presupuesto bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    id_tipo_gasto bigint NOT NULL,
    descripcion_item character varying(255) NOT NULL,
    cantidad numeric(12,2) NOT NULL,
    unidad_item character varying(30),
    precio_unitario numeric(15,2) NOT NULL
);


--
-- Name: mglpbc_item_presupuesto_id_item_presupuesto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_item_presupuesto_id_item_presupuesto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_item_presupuesto_id_item_presupuesto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_item_presupuesto_id_item_presupuesto_seq OWNED BY public.mglpbc_item_presupuesto.id_item_presupuesto;


--
-- Name: mglpbc_material; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_material (
    id_material bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    id_gasto bigint,
    nombre_material character varying(255) NOT NULL,
    unidad_material character varying(50) NOT NULL,
    stock_actual numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    stock_inicial numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    stock_minimo numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    costo_estimado_material numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    estado_material smallint DEFAULT '1'::smallint NOT NULL,
    id_item_presupuesto bigint
);


--
-- Name: mglpbc_material_id_material_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_material_id_material_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_material_id_material_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_material_id_material_seq OWNED BY public.mglpbc_material.id_material;


--
-- Name: mglpbc_movimiento_inventario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_movimiento_inventario (
    id_movimiento bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    id_material bigint NOT NULL,
    tipo_movimiento character varying(255) NOT NULL,
    cantidad_movimiento numeric(12,2) NOT NULL,
    fecha_movimiento date NOT NULL,
    descripcion_movimiento text,
    CONSTRAINT mglpbc_movimiento_inventario_tipo_movimiento_check CHECK (((tipo_movimiento)::text = ANY ((ARRAY['ENTRADA'::character varying, 'SALIDA'::character varying, 'AJUSTE'::character varying])::text[])))
);


--
-- Name: mglpbc_movimiento_inventario_id_movimiento_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_movimiento_inventario_id_movimiento_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_movimiento_inventario_id_movimiento_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_movimiento_inventario_id_movimiento_seq OWNED BY public.mglpbc_movimiento_inventario.id_movimiento;


--
-- Name: mglpbc_proyecto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_proyecto (
    id_proyecto bigint NOT NULL,
    id_usuario bigint NOT NULL,
    nombre_proyecto character varying(255) NOT NULL,
    descripcion_proyecto text,
    fecha_inicio_proyecto date NOT NULL,
    fecha_termino_proyecto date,
    estado_proyecto smallint DEFAULT '1'::smallint NOT NULL,
    presupuesto_proyecto numeric(15,2) NOT NULL
);


--
-- Name: mglpbc_proyecto_id_proyecto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_proyecto_id_proyecto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_proyecto_id_proyecto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_proyecto_id_proyecto_seq OWNED BY public.mglpbc_proyecto.id_proyecto;


--
-- Name: mglpbc_tipo_costo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_tipo_costo (
    id_tipo_costo bigint NOT NULL,
    nombre_tipo_costo character varying(100) NOT NULL
);


--
-- Name: mglpbc_tipo_costo_id_tipo_costo_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_tipo_costo_id_tipo_costo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_tipo_costo_id_tipo_costo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_tipo_costo_id_tipo_costo_seq OWNED BY public.mglpbc_tipo_costo.id_tipo_costo;


--
-- Name: mglpbc_tipo_gastos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_tipo_gastos (
    id_tipo_gasto bigint NOT NULL,
    nombre_tipo_gasto character varying(100) NOT NULL
);


--
-- Name: mglpbc_tipo_gastos_id_tipo_gasto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_tipo_gastos_id_tipo_gasto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_tipo_gastos_id_tipo_gasto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_tipo_gastos_id_tipo_gasto_seq OWNED BY public.mglpbc_tipo_gastos.id_tipo_gasto;


--
-- Name: mglpbc_usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mglpbc_usuario (
    id_usuario bigint NOT NULL,
    nombre_usuario character varying(100) NOT NULL,
    apellido_paterno character varying(100) NOT NULL,
    apellido_materno character varying(100),
    correo_usuario character varying(255) NOT NULL,
    password_usuario character varying(255) NOT NULL,
    rol_usuario character varying(50) NOT NULL,
    estado_usuario smallint DEFAULT '1'::smallint NOT NULL,
    remember_token character varying(100)
);


--
-- Name: mglpbc_usuario_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mglpbc_usuario_id_usuario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mglpbc_usuario_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mglpbc_usuario_id_usuario_seq OWNED BY public.mglpbc_usuario.id_usuario;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: mglpbc_auditoria id_auditoria; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_auditoria ALTER COLUMN id_auditoria SET DEFAULT nextval('public.mglpbc_auditoria_id_auditoria_seq'::regclass);


--
-- Name: mglpbc_curva_s id_curva; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_curva_s ALTER COLUMN id_curva SET DEFAULT nextval('public.mglpbc_curva_s_id_curva_seq'::regclass);


--
-- Name: mglpbc_gastos id_gasto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos ALTER COLUMN id_gasto SET DEFAULT nextval('public.mglpbc_gastos_id_gasto_seq'::regclass);


--
-- Name: mglpbc_item_presupuesto id_item_presupuesto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_item_presupuesto ALTER COLUMN id_item_presupuesto SET DEFAULT nextval('public.mglpbc_item_presupuesto_id_item_presupuesto_seq'::regclass);


--
-- Name: mglpbc_material id_material; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_material ALTER COLUMN id_material SET DEFAULT nextval('public.mglpbc_material_id_material_seq'::regclass);


--
-- Name: mglpbc_movimiento_inventario id_movimiento; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_movimiento_inventario ALTER COLUMN id_movimiento SET DEFAULT nextval('public.mglpbc_movimiento_inventario_id_movimiento_seq'::regclass);


--
-- Name: mglpbc_proyecto id_proyecto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_proyecto ALTER COLUMN id_proyecto SET DEFAULT nextval('public.mglpbc_proyecto_id_proyecto_seq'::regclass);


--
-- Name: mglpbc_tipo_costo id_tipo_costo; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_tipo_costo ALTER COLUMN id_tipo_costo SET DEFAULT nextval('public.mglpbc_tipo_costo_id_tipo_costo_seq'::regclass);


--
-- Name: mglpbc_tipo_gastos id_tipo_gasto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_tipo_gastos ALTER COLUMN id_tipo_gasto SET DEFAULT nextval('public.mglpbc_tipo_gastos_id_tipo_gasto_seq'::regclass);


--
-- Name: mglpbc_usuario id_usuario; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_usuario ALTER COLUMN id_usuario SET DEFAULT nextval('public.mglpbc_usuario_id_usuario_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Data for Name: mglpbc_auditoria; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: mglpbc_curva_s; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: mglpbc_gastos; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_gastos (id_gasto, id_proyecto, id_tipo_gasto, id_tipo_costo, nombre_gasto, descripcion_gasto, monto_gasto, fecha_gasto, documento_gasto, cantidad_gasto, id_item_presupuesto, unidad_gasto, estado_gasto, id_usuario_registro, id_usuario_revisor, fecha_revision, motivo_rechazo) VALUES (23, 7, 1, 1, 'CEMENTO', NULL, 232000.00, '2026-07-01', 'gastos/gasto_1782961958_9797.pdf', 10.00, 1, NULL, 'aprobado', 1, 1, NULL, NULL);
INSERT INTO public.mglpbc_gastos (id_gasto, id_proyecto, id_tipo_gasto, id_tipo_costo, nombre_gasto, descripcion_gasto, monto_gasto, fecha_gasto, documento_gasto, cantidad_gasto, id_item_presupuesto, unidad_gasto, estado_gasto, id_usuario_registro, id_usuario_revisor, fecha_revision, motivo_rechazo) VALUES (22, 7, 1, 1, 'Pintura Latex', NULL, 25000000.00, '2026-07-01', NULL, 200.00, 1, NULL, 'aprobado', 1, 1, NULL, NULL);
INSERT INTO public.mglpbc_gastos (id_gasto, id_proyecto, id_tipo_gasto, id_tipo_costo, nombre_gasto, descripcion_gasto, monto_gasto, fecha_gasto, documento_gasto, cantidad_gasto, id_item_presupuesto, unidad_gasto, estado_gasto, id_usuario_registro, id_usuario_revisor, fecha_revision, motivo_rechazo) VALUES (21, 7, 2, 1, 'Personal de Obras', 'Contratacion de personal', 10000000.00, '2026-07-01', NULL, 0.00, 1, NULL, 'aprobado', 1, 1, NULL, NULL);


--
-- Data for Name: mglpbc_item_presupuesto; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_item_presupuesto (id_item_presupuesto, id_proyecto, id_tipo_gasto, descripcion_item, cantidad, unidad_item, precio_unitario) VALUES (1, 7, 9, 'Presupuesto general', 1.00, 'global', 500000000.00);


--
-- Data for Name: mglpbc_material; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_material (id_material, id_proyecto, id_gasto, nombre_material, unidad_material, stock_actual, stock_inicial, stock_minimo, costo_estimado_material, estado_material, id_item_presupuesto) VALUES (10, 7, NULL, 'Pintura Latex', 'Galones', 100.00, 200.00, 5.00, 25000000.00, 1, NULL);
INSERT INTO public.mglpbc_material (id_material, id_proyecto, id_gasto, nombre_material, unidad_material, stock_actual, stock_inicial, stock_minimo, costo_estimado_material, estado_material, id_item_presupuesto) VALUES (11, 7, NULL, 'CEMENTO', 'Sacos', 10.00, 10.00, 5.00, 232000.00, 1, NULL);


--
-- Data for Name: mglpbc_movimiento_inventario; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_movimiento_inventario (id_movimiento, id_proyecto, id_material, tipo_movimiento, cantidad_movimiento, fecha_movimiento, descripcion_movimiento) VALUES (4, 7, 10, 'SALIDA', 100.00, '2026-07-01', 'Pintura Interior');
INSERT INTO public.mglpbc_movimiento_inventario (id_movimiento, id_proyecto, id_material, tipo_movimiento, cantidad_movimiento, fecha_movimiento, descripcion_movimiento) VALUES (5, 7, 10, 'ENTRADA', 200.00, '2026-07-01', 'Saldo inicial (ingresado antes del historial de movimientos)');
INSERT INTO public.mglpbc_movimiento_inventario (id_movimiento, id_proyecto, id_material, tipo_movimiento, cantidad_movimiento, fecha_movimiento, descripcion_movimiento) VALUES (6, 7, 11, 'ENTRADA', 10.00, '2026-07-01', 'Saldo inicial (ingresado antes del historial de movimientos)');


--
-- Data for Name: mglpbc_proyecto; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_proyecto (id_proyecto, id_usuario, nombre_proyecto, descripcion_proyecto, fecha_inicio_proyecto, fecha_termino_proyecto, estado_proyecto, presupuesto_proyecto) VALUES (7, 1, 'MacDonalds Norte', NULL, '2026-07-01', '2027-10-06', 1, 500000000.00);


--
-- Data for Name: mglpbc_tipo_costo; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_tipo_costo (id_tipo_costo, nombre_tipo_costo) VALUES (1, 'Normal');
INSERT INTO public.mglpbc_tipo_costo (id_tipo_costo, nombre_tipo_costo) VALUES (2, 'Extra');


--
-- Data for Name: mglpbc_tipo_gastos; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (1, 'Materiales');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (2, 'Mano de Obra');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (3, 'Maquinaria');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (4, 'Transporte');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (5, 'Herramientas');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (6, 'Permisos');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (7, 'Servicios');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (8, 'Subcontratos');
INSERT INTO public.mglpbc_tipo_gastos (id_tipo_gasto, nombre_tipo_gasto) VALUES (9, 'Otros');


--
-- Data for Name: mglpbc_usuario; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.mglpbc_usuario (id_usuario, nombre_usuario, apellido_paterno, apellido_materno, correo_usuario, password_usuario, rol_usuario, estado_usuario, remember_token) VALUES (1, 'Benjamín', 'Contreras', NULL, 'admin@constructcontrol.cl', '$2y$12$cUpaqiWr3DSZTGCQQHYGZeLHemLr8hdoFAReM2uKjK6jW0lqJGdRm', 'Administrador', 1, 'Sozfu11aaOjsj3AJHSx8ySCaV0pAlVDtRRkP3MShPEQvRhxIrIcvRuV68S7n');


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.migrations (id, migration, batch) VALUES (1, '0001_01_01_000000_create_sessions_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (4, '2026_11_02_000001_create_mglpbc_usuario_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (5, '2026_11_02_000002_create_mglpbc_tipo_gastos_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (6, '2026_11_02_000003_create_mglpbc_tipo_costo_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (7, '2026_11_02_000004_create_mglpbc_proyecto_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (8, '2026_11_02_000005_create_mglpbc_gastos_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (9, '2026_11_02_000006_create_mglpbc_curva_s_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (10, '2026_11_02_000007_create_mglpbc_material_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (11, '2026_11_02_000008_create_mglpbc_item_presupuesto_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (12, '2026_11_02_000009_create_mglpbc_movimiento_inventario_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (14, '2026_11_03_000001_create_password_reset_tokens_table', 2);
INSERT INTO public.migrations (id, migration, batch) VALUES (16, '2026_11_03_000003_convertir_items_de_presupuesto', 3);
INSERT INTO public.migrations (id, migration, batch) VALUES (17, '2026_11_03_000004_agregar_aprobacion_a_gastos', 3);
INSERT INTO public.migrations (id, migration, batch) VALUES (18, '2026_11_03_000005_inventario_con_presupuesto_y_conteo', 3);
INSERT INTO public.migrations (id, migration, batch) VALUES (19, '2026_11_03_000006_create_mglpbc_auditoria_table', 3);


--
-- Name: mglpbc_auditoria_id_auditoria_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_auditoria_id_auditoria_seq', 1, false);


--
-- Name: mglpbc_curva_s_id_curva_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_curva_s_id_curva_seq', 1, true);


--
-- Name: mglpbc_gastos_id_gasto_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_gastos_id_gasto_seq', 23, true);


--
-- Name: mglpbc_item_presupuesto_id_item_presupuesto_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_item_presupuesto_id_item_presupuesto_seq', 1, true);


--
-- Name: mglpbc_material_id_material_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_material_id_material_seq', 11, true);


--
-- Name: mglpbc_movimiento_inventario_id_movimiento_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_movimiento_inventario_id_movimiento_seq', 6, true);


--
-- Name: mglpbc_proyecto_id_proyecto_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_proyecto_id_proyecto_seq', 7, true);


--
-- Name: mglpbc_tipo_costo_id_tipo_costo_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_tipo_costo_id_tipo_costo_seq', 2, true);


--
-- Name: mglpbc_tipo_gastos_id_tipo_gasto_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_tipo_gastos_id_tipo_gasto_seq', 9, true);


--
-- Name: mglpbc_usuario_id_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.mglpbc_usuario_id_usuario_seq', 1, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 19, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: mglpbc_auditoria mglpbc_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_auditoria
    ADD CONSTRAINT mglpbc_auditoria_pkey PRIMARY KEY (id_auditoria);


--
-- Name: mglpbc_curva_s mglpbc_curva_s_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_curva_s
    ADD CONSTRAINT mglpbc_curva_s_pkey PRIMARY KEY (id_curva);


--
-- Name: mglpbc_gastos mglpbc_gastos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_pkey PRIMARY KEY (id_gasto);


--
-- Name: mglpbc_item_presupuesto mglpbc_item_presupuesto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_item_presupuesto
    ADD CONSTRAINT mglpbc_item_presupuesto_pkey PRIMARY KEY (id_item_presupuesto);


--
-- Name: mglpbc_material mglpbc_material_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_material
    ADD CONSTRAINT mglpbc_material_pkey PRIMARY KEY (id_material);


--
-- Name: mglpbc_movimiento_inventario mglpbc_movimiento_inventario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_movimiento_inventario
    ADD CONSTRAINT mglpbc_movimiento_inventario_pkey PRIMARY KEY (id_movimiento);


--
-- Name: mglpbc_proyecto mglpbc_proyecto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_proyecto
    ADD CONSTRAINT mglpbc_proyecto_pkey PRIMARY KEY (id_proyecto);


--
-- Name: mglpbc_tipo_costo mglpbc_tipo_costo_nombre_tipo_costo_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_tipo_costo
    ADD CONSTRAINT mglpbc_tipo_costo_nombre_tipo_costo_unique UNIQUE (nombre_tipo_costo);


--
-- Name: mglpbc_tipo_costo mglpbc_tipo_costo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_tipo_costo
    ADD CONSTRAINT mglpbc_tipo_costo_pkey PRIMARY KEY (id_tipo_costo);


--
-- Name: mglpbc_tipo_gastos mglpbc_tipo_gastos_nombre_tipo_gasto_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_tipo_gastos
    ADD CONSTRAINT mglpbc_tipo_gastos_nombre_tipo_gasto_unique UNIQUE (nombre_tipo_gasto);


--
-- Name: mglpbc_tipo_gastos mglpbc_tipo_gastos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_tipo_gastos
    ADD CONSTRAINT mglpbc_tipo_gastos_pkey PRIMARY KEY (id_tipo_gasto);


--
-- Name: mglpbc_usuario mglpbc_usuario_correo_usuario_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_usuario
    ADD CONSTRAINT mglpbc_usuario_correo_usuario_unique UNIQUE (correo_usuario);


--
-- Name: mglpbc_usuario mglpbc_usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_usuario
    ADD CONSTRAINT mglpbc_usuario_pkey PRIMARY KEY (id_usuario);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: mglpbc_auditoria_entidad_id_registro_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mglpbc_auditoria_entidad_id_registro_index ON public.mglpbc_auditoria USING btree (entidad, id_registro);


--
-- Name: mglpbc_auditoria_fecha_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mglpbc_auditoria_fecha_index ON public.mglpbc_auditoria USING btree (fecha);


--
-- Name: mglpbc_auditoria_id_proyecto_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mglpbc_auditoria_id_proyecto_index ON public.mglpbc_auditoria USING btree (id_proyecto);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: mglpbc_auditoria mglpbc_auditoria_id_usuario_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_auditoria
    ADD CONSTRAINT mglpbc_auditoria_id_usuario_foreign FOREIGN KEY (id_usuario) REFERENCES public.mglpbc_usuario(id_usuario) ON DELETE SET NULL;


--
-- Name: mglpbc_curva_s mglpbc_curva_s_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_curva_s
    ADD CONSTRAINT mglpbc_curva_s_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.mglpbc_proyecto(id_proyecto) ON DELETE CASCADE;


--
-- Name: mglpbc_gastos mglpbc_gastos_id_item_presupuesto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_id_item_presupuesto_foreign FOREIGN KEY (id_item_presupuesto) REFERENCES public.mglpbc_item_presupuesto(id_item_presupuesto) ON DELETE RESTRICT;


--
-- Name: mglpbc_gastos mglpbc_gastos_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.mglpbc_proyecto(id_proyecto) ON DELETE CASCADE;


--
-- Name: mglpbc_gastos mglpbc_gastos_id_tipo_costo_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_id_tipo_costo_foreign FOREIGN KEY (id_tipo_costo) REFERENCES public.mglpbc_tipo_costo(id_tipo_costo);


--
-- Name: mglpbc_gastos mglpbc_gastos_id_tipo_gasto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_id_tipo_gasto_foreign FOREIGN KEY (id_tipo_gasto) REFERENCES public.mglpbc_tipo_gastos(id_tipo_gasto);


--
-- Name: mglpbc_gastos mglpbc_gastos_id_usuario_registro_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_id_usuario_registro_foreign FOREIGN KEY (id_usuario_registro) REFERENCES public.mglpbc_usuario(id_usuario);


--
-- Name: mglpbc_gastos mglpbc_gastos_id_usuario_revisor_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_gastos
    ADD CONSTRAINT mglpbc_gastos_id_usuario_revisor_foreign FOREIGN KEY (id_usuario_revisor) REFERENCES public.mglpbc_usuario(id_usuario);


--
-- Name: mglpbc_item_presupuesto mglpbc_item_presupuesto_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_item_presupuesto
    ADD CONSTRAINT mglpbc_item_presupuesto_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.mglpbc_proyecto(id_proyecto) ON DELETE CASCADE;


--
-- Name: mglpbc_item_presupuesto mglpbc_item_presupuesto_id_tipo_gasto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_item_presupuesto
    ADD CONSTRAINT mglpbc_item_presupuesto_id_tipo_gasto_foreign FOREIGN KEY (id_tipo_gasto) REFERENCES public.mglpbc_tipo_gastos(id_tipo_gasto);


--
-- Name: mglpbc_material mglpbc_material_id_gasto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_material
    ADD CONSTRAINT mglpbc_material_id_gasto_foreign FOREIGN KEY (id_gasto) REFERENCES public.mglpbc_gastos(id_gasto) ON DELETE SET NULL;


--
-- Name: mglpbc_material mglpbc_material_id_item_presupuesto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_material
    ADD CONSTRAINT mglpbc_material_id_item_presupuesto_foreign FOREIGN KEY (id_item_presupuesto) REFERENCES public.mglpbc_item_presupuesto(id_item_presupuesto) ON DELETE SET NULL;


--
-- Name: mglpbc_material mglpbc_material_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_material
    ADD CONSTRAINT mglpbc_material_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.mglpbc_proyecto(id_proyecto) ON DELETE CASCADE;


--
-- Name: mglpbc_movimiento_inventario mglpbc_movimiento_inventario_id_material_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_movimiento_inventario
    ADD CONSTRAINT mglpbc_movimiento_inventario_id_material_foreign FOREIGN KEY (id_material) REFERENCES public.mglpbc_material(id_material) ON DELETE CASCADE;


--
-- Name: mglpbc_movimiento_inventario mglpbc_movimiento_inventario_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_movimiento_inventario
    ADD CONSTRAINT mglpbc_movimiento_inventario_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.mglpbc_proyecto(id_proyecto) ON DELETE CASCADE;


--
-- Name: mglpbc_proyecto mglpbc_proyecto_id_usuario_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mglpbc_proyecto
    ADD CONSTRAINT mglpbc_proyecto_id_usuario_foreign FOREIGN KEY (id_usuario) REFERENCES public.mglpbc_usuario(id_usuario);


--
-- PostgreSQL database dump complete
--


