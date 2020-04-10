#/usr/bin/env bash

# bash autocomplete provided by scrinus web&co GmbH (https://www.webundco.com)
# for Neos flow framework (https://flow.neos.io/de)

_flow_completions()
{
  COMP_LEN=${#COMP_WORDS[@]}
  FLOWBIN=${COMP_WORDS[0]}

  if [ "${COMP_WORDS[1]}" == "help" ]; then
      return
  fi

  if [[ "$COMP_LEN" -le "4" || ( "${COMP_WORDS[4]}" == ":"  && "$COMP_LEN" -le "6" ) ]]; then
      # handle the controller that is used

      # the colon ':' character is replaced by a paragraph '§'
      # because the colon is a separator for `complete` or `compgen`

      W=${COMP_WORDS[1]}

      WITHCOLON=0
      NEEDLE=""
      if [[ $COMP_LEN -ge 3 && "${COMP_WORDS[2]}" == ":" ]]; then
          W="$W:"

          WITHCOLON=1
          NEEDLE="${COMP_WORDS[1]}:"

          if [[ $COMP_LEN -ge 4 ]]; then
              W="$W${COMP_WORDS[3]}"

              if [[ "${COMP_WORDS[4]}" == ":" ]]; then
                  NEEDLE="$NEEDLE${COMP_WORDS[3]}:"
              fi

              if [[ $COMP_LEN -ge 6 ]]; then
                  #NEEDLE="$NEEDLE${COMP_WORDS[5]}"
                  W="$W:${COMP_WORDS[5]}"
              fi
          fi
      fi

      H=$($FLOWBIN help | grep -o -e '^[\* ] [a-zA-Z0-9]\+\(:[a-zA-Z0-9]\+\)*' | cut -c 3- )
      H=${H//:/§}

      W=${W//:/§}

      X=$(compgen -W "$H" "$W")
      if [ "$WITHCOLON" -eq "1" ]; then
          NEEDLE=${NEEDLE//:/§}

          X=${X//$NEEDLE/}
      fi
      X=${X//§/:}

      COMPREPLY=($X)
  else
      # process command line controller arguments
      WIDX="$(($COMP_LEN-1))"

      W=${COMP_WORDS[$WIDX]}
      CONTROLLER="${COMP_WORDS[1]}:${COMP_WORDS[3]}"
      if [[ $COMP_LEN -ge 5 && "${COMP_WORDS[4]}" == ":" ]]; then
          CONTROLLER="$CONTROLLER:${COMP_WORDS[5]}"
      fi
      H=$($FLOWBIN help $CONTROLLER | grep -o -e '  --[-a-zA-Z0-9]\+')
      X=$(compgen -W "$H" -- "$W")
      COMPREPLY=($X)
  fi
}
complete -F _flow_completions flow
