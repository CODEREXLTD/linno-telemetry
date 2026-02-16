# Project TODOs

- [ ] Implement value-based thresholds for KUI, allowing for aggregation of numeric values (e.g., total sales amount) over a period, in addition to the existing count-based thresholds. This will involve modifying the `kui` configuration structure, and updating logic in `TriggerManager` and `Queue` for value extraction, aggregation, and comparison against a defined value threshold.
