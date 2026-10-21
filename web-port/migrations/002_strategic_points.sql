CREATE TABLE `strategic_events` (
	`id` integer PRIMARY KEY NOT NULL,
	`starts` integer NOT NULL,
	`ends` integer NOT NULL,
	`hp` integer DEFAULT 1000 NOT NULL
);

CREATE TABLE `strategic_orders` (
	`id` text PRIMARY KEY NOT NULL,
	`event_id` integer NOT NULL,
	`pilot_id` text NOT NULL,
	`nation` text NOT NULL,
	`at` integer NOT NULL,
	`delta` integer NOT NULL,
	FOREIGN KEY (`event_id`) REFERENCES `strategic_events`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`pilot_id`) REFERENCES `pilots`(`id`) ON UPDATE no action ON DELETE no action
);
