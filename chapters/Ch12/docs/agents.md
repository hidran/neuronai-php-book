# Agents in NeuronAI

An Agent is a class extending `NeuronAI\Agent\Agent`. It is a pre-configured
Workflow, which is why anything a Workflow can do an Agent can also do.

## The agent loop

The loop is: call the model, run whatever tools it asks for, hand the results
back, and repeat until the model returns prose instead of another tool call.
There is no default ceiling on the number of iterations. You bound it with
`toolMaxRuns()` on the agent, or `setMaxRuns()` on an individual tool.

A run limit that keeps being hit is a diagnostic about your tool design, not a
number to raise.

## The three methods that matter

`provider()` returns the model. `instructions()` returns the system prompt.
`chatHistory()` returns the conversation store. Everything else is optional.

## Context window

The context window is a hard ceiling covering the system prompt, the history,
the tool schemas and the response together. Configure the trimmer five to ten
per cent below the model's real limit: the trimmer needs headroom to pick a
sensible cut point rather than a mechanical one.
